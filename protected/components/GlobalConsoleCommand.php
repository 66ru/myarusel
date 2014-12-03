<?php

/**
 * Class GlobalConsoleCommand
 *
 * version 3.0
 *
 * @property CDbConnection dbConnection
 * @property CDbCommandBuilder commandBuilder
 * @property string taskName
 *
 * @method captureException(Exception $e, array $additionalData = [])
 * @method captureMessage(string $message, array $additionalData = [])
 * @method setContext(array $data = [])
 * @method log(string $text)
 */
abstract class GlobalConsoleCommand extends CConsoleCommand
{
    /**
     * If process executes more than $longRunningTimeout seconds,
     * it will be considered as long-running
     * and warning message will be logged
     * @var int
     */
    public $longRunningTimeout = 3600; // 1h

    /**
     * If any running task $lastActivity value older than $changeOwnerTimeout,
     * this lock will be released, so
     * this task will be acquired by any of active nodes.
     * @var int seconds
     */
    public $changeOwnerTimeout = 7200; // 2h

    /**
     * @var CronLock
     */
    protected $lock;

    public function init()
    {
        parent::init();

        $this->refreshActivity();
        $this->onBeforeAction = array($this, 'checkInstance');
        $this->onAfterAction = array($this, 'cleanUp');
    }

    public function behaviors()
    {
        return [
            'log' => [
                'class' => 'application.components.ConsoleLoggingBehavior',
            ]
        ];
    }

    public function refreshActivity()
    {
        try {
            $criteria = new CDbCriteria();
            $criteria->addColumnCondition(array('hostname' => gethostname()));
            CronLock::model()->updateAll(
                array(
                    'lastActivity' => new CDbExpression('NOW()'),
                ),
                $criteria
            );
        } catch (CDbException $e) {
            throw new GlobalCommandException('failed update lastActivity', 0, $e);
        }
    }

    /**
     * @param CConsoleCommandEvent $event
     * @throws GlobalCommandException
     */
    protected function cleanUp($event)
    {
        $command = $event->sender;
        if (!($command instanceof GlobalConsoleCommand)) {
            throw new GlobalCommandException('$event->sender property must be GlobalConsoleCommand instance');
        }

        if ($event->exitCode == 0) {
            try {
                $attributes = $this->lock->attributes;
                unset($attributes['lastActivity']);
                if (!CronLock::model()->deleteAllByAttributes($attributes)) {
                    $this->setContext($this->lock->attributes);
                    throw new GlobalCommandException('failed to release lock');
                }
            } catch (CDbException $e) {
                throw new GlobalCommandException('failed to release lock', 0, $e);
            }
        }
    }

    /**
     * @return string
     */
    public function getTaskName()
    {
        return str_replace('Command', '', get_class($this));
    }

    /**
     * @param CConsoleCommandEvent $event
     */
    protected function checkInstance($event)
    {
        $event->stopCommand |= $this->checkTaskRunning();
        $this->releaseHangedNodes();
        $event->stopCommand |= !$this->lockAndStartTask();
    }

    /**
     * @return bool
     * @throws GlobalCommandException
     */
    protected function checkTaskRunning()
    {
        /** @var CronLock $task */
        $task = CronLock::model()->findByAttributes(array('hostname' => gethostname(), 'taskName' => $this->taskName));
        if ($task) {
            $processStartedTime = @filectime('/proc/' . $task->pid);
            if (!$processStartedTime) { // process missing –> postprocess
                try {
                    if (!$task->delete()) {
                        $this->setContext($task->errors);
                        throw new GlobalCommandException('failed to remove lock');
                    }
                } catch (CDbException $e) {
                    $this->setContext($task->attributes);
                    throw new GlobalCommandException('failed to remove lock', 0, $e);
                }
            } elseif ($processStartedTime + $this->longRunningTimeout < time()) {
                $e = new GlobalCommandException('task freezed');
                $e->severety = E_WARNING;
                $this->captureException($e, $task->attributes);
                return true;
            }
        }

        return false;
    }

    /**
     * @throws GlobalCommandException
     */
    protected function releaseHangedNodes()
    {
        try {
            /** @var CronLock[] $expiredTasks */
            $expiredTasks = CronLock::model()->findAll(
                'lastActivity <= NOW() - INTERVAL :changeOwnerTimeout SECOND',
                array(
                    ':changeOwnerTimeout' => $this->changeOwnerTimeout,
                )
            );
        } catch (CDbException $e) {
            throw new GlobalCommandException('failed fetch expired task', 0, $e);
        }

        foreach ($expiredTasks as $task) {
            try {
                if (!$task->delete()) {
                    $this->setContext($task->attributes);
                    throw new GlobalCommandException('failed to release lock');
                }

                $e = new GlobalCommandException('task lock was released');
                $e->severety = E_WARNING;
                $additionalData = $task->attributes;
                $additionalData['releasedBy'] = gethostname();
                $this->captureException($e, $additionalData);
            } catch (CDbException $e) {
                throw new GlobalCommandException('failed to release lock', 0, $e);
            }
        }
    }

    /**
     * @return bool
     */
    protected function lockAndStartTask()
    {
        try {
            $this->lock = new CronLock();
            $this->lock->setAttributes(
                array(
                    'hostname' => gethostname(),
                    'taskName' => $this->taskName,
                    'pid' => posix_getpid(),
                    'lastActivity' => new CDbExpression('NOW()'),
                )
            );
            $this->lock->save();

            return true;
        } catch (CDbException $e) {
            return false;
        }
    }
}

class GlobalCommandException extends Exception
{
    public $severety = E_ERROR;

    public function getSeverity()
    {
        return $this->severety;
    }
}
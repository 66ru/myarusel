<?php

/**
 * Class SentryLoggingBehavior
 *
// * @method captureException(Exception $e, array $additionalData = [])
// * @method captureMessage(string $message, array $additionalData = [])
// * @method setContext(array $data = [])
// * @method log(string $text)
 */
class ConsoleLoggingBehavior extends CConsoleCommandBehavior
{
    /**
     * @param Exception $e
     * @param array $additionalData
     */
    public function captureException($e, $additionalData = [])
    {
        /** @var ESentryComponent $sentry */
        $sentry = Yii::app()->getComponent('RSentryException');
        if ($sentry) {
            $sentry->captureException($e, $additionalData);
        }

        if (YII_DEBUG && Yii::app() instanceof CConsoleApplication) {
            echo $e."\n";
        }
    }

    /**
     * @param string $message
     * @param array $additionalData
     */
    public function captureMessage($message, $additionalData = [])
    {
        /** @var ESentryComponent $sentry */
        $sentry = Yii::app()->getComponent('RSentryException');
        if ($sentry) {
            $sentry->getClient()->extra_context($additionalData);
            $sentry->getClient()->captureMessage($message);
            $sentry->getClient()->context->clear();
        }

        if (YII_DEBUG && Yii::app() instanceof CConsoleApplication) {
            echo $message."\n";
        }
    }

    /**
     * @param array $data
     */
    public function setContext($data = [])
    {
        /** @var ESentryComponent $sentry */
        $sentry = Yii::app()->getComponent('RSentryException');
        if ($sentry) {
            $sentry->setContext($data);
        }
    }

    /**
     * @param $text
     */
    public function log($text)
    {
        if (YII_DEBUG) {
            echo date('r') . ": " . $text . "\n";
        }
    }
}
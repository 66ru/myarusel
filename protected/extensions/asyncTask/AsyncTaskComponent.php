<?php

/**
 * Class AsyncTaskComponent
 *
 * @property-read IAsyncDriver $driver
 * @property-read IAsyncTaskProvider $provider
 */
class AsyncTaskComponent extends CApplicationComponent
{
    public $providerClass = 'AsyncTaskGlobalStateProvider';
    public $driverClass = 'FpmAsyncDriver';

    /**
     * @var IAsyncDriver
     */
    protected $driver;
    /**
     * @var IAsyncTaskProvider
     */
    protected $provider;

    public function init()
    {
        parent::init();

        Yii::setPathOfAlias('asyncTask', __DIR__);
        Yii::import('asyncTask.providers.*');
        Yii::import('asyncTask.drivers.*');

        $this->driver = new $this->driverClass();
        $this->provider = new $this->providerClass();
    }

    /**
     * @return IAsyncDriver
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * @return IAsyncTaskProvider
     */
    public function getProvider()
    {
        return $this->provider;
    }
}
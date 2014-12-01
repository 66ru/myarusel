<?php

/**
 * Class CronLock
 *
 * @property string $taskName unique task id
 * @property string $hostname task owner node's hostname
 * @property string $lastActivity datetime of last detected process activity
 * @property int $pid
 */
class CronLock extends CActiveRecord
{
    /**
     * @param string $className
     * @return CronLock
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function rules()
    {
        return array(
            array('taskName, hostname, lastActivity, pid', 'safe'),
        );
    }
}

/**
CREATE TABLE `CronLock` (
`taskName` varchar(255) NOT NULL,
`hostname` varchar(255) NOT NULL,
`lastActivity` datetime NOT NULL,
`pid` int(11) unsigned NOT NULL,
PRIMARY KEY (`taskName`),
KEY `hostname` (`hostname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
*/

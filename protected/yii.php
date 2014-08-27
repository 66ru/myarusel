<?php
/**
 * Yii bootstrap file.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @link http://www.yiiframework.com/
 * @copyright Copyright &copy; 2008-2011 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 * @version $Id$
 * @package system
 * @since 1.0
 */

require(__DIR__ . '/../lib/yii/framework/YiiBase.php');

/**
 * Yii is a helper class serving common framework functionalities.
 *
 * It encapsulates {@link YiiBase} which provides the actual implementation.
 * By writing your own Yii class, you can customize some functionalities of YiiBase.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @version $Id$
 * @package system
 * @since 1.0
 */
class Yii extends YiiBase
{
    /**
     * @return WebApplication|CConsoleApplication
     */
    public static function app()
    {
        return parent::app();
    }
}

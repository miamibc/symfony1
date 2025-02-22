<?php

/*
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfNoLogger is a noop logger.
 *
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class sfNoLogger extends sfLogger
{
    /**
     * Initializes this logger.
     *
     * @param sfEventDispatcher $dispatcher A sfEventDispatcher instance
     * @param array             $options    an array of options
     */
    public function initialize(sfEventDispatcher $dispatcher, $options = array())
    {
    }

    /**
     * Logs a message.
     *
     * @param string $message  Message
     * @param int    $priority Message priority
     */
    protected function doLog($message, $priority)
    {
    }
}

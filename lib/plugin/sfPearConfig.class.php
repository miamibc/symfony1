<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once 'PEAR/Config.php';

/**
 * sfPearConfig.
 *
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class sfPearConfig extends PEAR_Config
{
    public function &getREST($version, $options = array())
    {
        $class = 'sfPearRest'.str_replace('.', '', $version);

        return new $class($this, $options);
    }
}

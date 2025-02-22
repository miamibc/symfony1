<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfWidgetFormSelectMany represents a select HTML tag where you can select multiple values.
 *
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class sfWidgetFormSelectMany extends sfWidgetFormSelect
{
    /**
     * Configures the current widget.
     *
     * @param array $options    An array of options
     * @param array $attributes An array of default HTML attributes
     *
     * @see sfWidgetFormSelect
     */
    protected function configure($options = array(), $attributes = array())
    {
        parent::configure($options, $attributes);

        $this->setOption('multiple', true);
    }
}

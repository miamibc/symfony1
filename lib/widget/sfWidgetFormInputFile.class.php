<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfWidgetFormInputFile represents an upload HTML input tag.
 *
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class sfWidgetFormInputFile extends sfWidgetFormInput
{
    /**
     * Configures the current widget.
     *
     * @param array $options    An array of options
     * @param array $attributes An array of default HTML attributes
     *
     * @see sfWidgetFormInput
     */
    protected function configure($options = array(), $attributes = array())
    {
        parent::configure($options, $attributes);

        $this->setOption('type', 'file');
        $this->setOption('needs_multipart', true);
    }
}

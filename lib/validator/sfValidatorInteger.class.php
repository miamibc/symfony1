<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfValidatorInteger validates an integer. It also converts the input value to an integer.
 *
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class sfValidatorInteger extends sfValidatorBase
{
    /**
     * Configures the current validator.
     *
     * Available options:
     *
     *  * max: The maximum value allowed
     *  * min: The minimum value allowed
     *
     * Available error codes:
     *
     *  * max
     *  * min
     *
     * @param array $options  An array of options
     * @param array $messages An array of error messages
     *
     * @see sfValidatorBase
     */
    protected function configure($options = array(), $messages = array())
    {
        $this->addMessage('max', '"%value%" must be at most %max%.');
        $this->addMessage('min', '"%value%" must be at least %min%.');

        $this->addOption('min');
        $this->addOption('max');

        $this->setMessage('invalid', '"%value%" is not an integer.');
    }

    /**
     * @see sfValidatorBase
     */
    protected function doClean($value)
    {
        $clean = (int) $value;

        if ((string) $clean != $value) {
            throw new sfValidatorError($this, 'invalid', array('value' => $value));
        }

        if ($this->hasOption('max') && $clean > $this->getOption('max')) {
            throw new sfValidatorError($this, 'max', array('value' => $value, 'max' => $this->getOption('max')));
        }

        if ($this->hasOption('min') && $clean < $this->getOption('min')) {
            throw new sfValidatorError($this, 'min', array('value' => $value, 'min' => $this->getOption('min')));
        }

        return $clean;
    }
}

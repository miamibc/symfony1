<?php

/*
 * This file is part of the symfony package.
 * (c) Fabien Potencier <fabien.potencier@symfony-project.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * sfValidatorIp validate an IP address.
 *
 * @author     Fabien Potencier <fabien.potencier@symfony-project.com>
 */
class sfValidatorIp extends sfValidatorString
{
    public const IP_V4 = '4';
    public const IP_V6 = '6';
    public const IP_ALL = 'all';

    // adds FILTER_FLAG_NO_PRIV_RANGE flag (skip private ranges)
    public const IP_V4_NO_PRIV = '4_no_priv';
    public const IP_V6_NO_PRIV = '6_no_priv';
    public const IP_ALL_NO_PRIV = 'all_no_priv';

    // adds FILTER_FLAG_NO_RES_RANGE flag (skip reserved ranges)
    public const IP_V4_NO_RES = '4_no_res';
    public const IP_V6_NO_RES = '6_no_res';
    public const IP_ALL_NO_RES = 'all_no_res';

    // adds FILTER_FLAG_NO_PRIV_RANGE and FILTER_FLAG_NO_RES_RANGE flags (skip both)
    public const IP_V4_ONLY_PUBLIC = '4_public';
    public const IP_V6_ONLY_PUBLIC = '6_public';
    public const IP_ALL_ONLY_PUBLIC = 'all_public';

    public function configure($options = array(), $messages = array())
    {
        $this->addOption('version', self::IP_ALL);

        parent::configure($options, $messages);

        $this->setMessage('invalid', 'Ip address is invalid');
    }

    protected function doClean($value)
    {
        $value = parent::doClean($value);

        switch ($this->getOption('version')) {
            case self::IP_V4:
                $flag = FILTER_FLAG_IPV4;

                break;

            case self::IP_V6:
                $flag = FILTER_FLAG_IPV6;

                break;

            case self::IP_V4_NO_PRIV:
                $flag = FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE;

                break;

            case self::IP_V6_NO_PRIV:
                $flag = FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE;

                break;

            case self::IP_ALL_NO_PRIV:
                $flag = FILTER_FLAG_NO_PRIV_RANGE;

                break;

            case self::IP_V4_NO_RES:
                $flag = FILTER_FLAG_IPV4 | FILTER_FLAG_NO_RES_RANGE;

                break;

            case self::IP_V6_NO_RES:
                $flag = FILTER_FLAG_IPV6 | FILTER_FLAG_NO_RES_RANGE;

                break;

            case self::IP_ALL_NO_RES:
                $flag = FILTER_FLAG_NO_RES_RANGE;

                break;

            case self::IP_V4_ONLY_PUBLIC:
                $flag = FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;

                break;

            case self::IP_V6_ONLY_PUBLIC:
                $flag = FILTER_FLAG_IPV6 | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;

                break;

            case self::IP_ALL_ONLY_PUBLIC:
                $flag = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;

                break;

            default:
                $flag = 0;

                break;
        }

        if (!filter_var($value, FILTER_VALIDATE_IP, $flag)) {
            throw new sfValidatorError($this, 'invalid', array('value' => $value));
        }

        return $value;
    }
}

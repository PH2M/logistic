<?php
/**
 * 2011-2017 PH2M
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0) that is available
 * through the world-wide-web at this URL: http://www.opensource.org/licenses/OSL-3.0
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to agence@reflet-digital.com so we can send you a copy immediately.
 *
 * @author PH2M - contact@ph2m.com
 * @copyright 2001-2017 PH2M
 * @license http://www.opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

namespace PH2M\Logistic\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Magento\Framework\Exception\ValidatorException;

/**
 * Class Connectiontype
 * @package PH2M\Logistic\Model\Config\Source
 */
class Connectiontype implements OptionSourceInterface
{
    const CONNECTION_TYPE_FTP = 'ftp';
    const CONNECTION_TYPE_SFTP = 'sftp';

    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => self::CONNECTION_TYPE_FTP, 'label' => __('FTP')],
            ['value' => self::CONNECTION_TYPE_SFTP, 'label' => __('SFTP')]
        ];
    }

    /**
     * @param $type
     * @throws ValidatorException
     */
    public function validateType($type)
    {
        $options = $this->toOptionArray();
        $values = array_column($options, 'value');

        if (array_search($type, $values) === false) {
            throw new ValidatorException(__('Connection type is invalid.'));
        }
    }
}
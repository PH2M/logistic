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

namespace PH2M\Logistic\Model\Log\Source;

use PH2M\Logistic\Model\Log;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Class Status
 * @package PH2M\Logistic\Model\Log\Source
 */
class Status implements OptionSourceInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'label' => __('Success'),
                'value' => Log::STATUS_SUCCESS
            ],
            [
                'label' => __('Error'),
                'value' => Log::STATUS_ERROR
            ]
        ];
    }
}
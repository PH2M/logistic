<?php
/**
 * 2011-2018 PH2M
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0) that is available
 * through the world-wide-web at this URL: http://www.opensource.org/licenses/OSL-3.0
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to contact@ph2m.com so we can send you a copy immediately.
 *
 * @author PH2M - contact@ph2m.com
 * @copyright 2011-2018 PH2M
 * @license http://www.opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

namespace PH2M\Logistic\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Filesystem\Io\Ftp;
use Magento\Framework\Filesystem\Io\Sftp;
use Magento\Framework\Filesystem\Io\File;
use Magento\Store\Model\ScopeInterface;
use PH2M\Logistic\Api\LogRepositoryInterface;
use PH2M\Logistic\Model\Config\Source\Connectiontype;
use PH2M\Logistic\Model\LogFactory;
use Magento\Framework\Filesystem\DirectoryList;

/**
 * Class AbstractImportExport
 * @package PH2M\Logistic\Model
 */
abstract class AbstractImportExport
{
    /**
     * @var string
     */
    protected $code = 'override_me';

    /**
     * @var Ftp
     */
    protected $ftp;

    /**
     * @var Sftp
     */
    protected $sftp;

    /**
     * @var File
     */
    protected $local;

    /**
     * @var Ftp|Sftp
     */
    protected $connection;

    /**
     * @var string
     */
    protected $fieldSeparator;

    /**
     * @var string
     */
    protected $fieldEnclosure;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Connectiontype
     */
    protected $connectionTypeSource;

    /**
     * @var LogRepositoryInterface
     */
    protected $logRepository;

    /**
     * @var LogFactory
     */
    protected $logFactory;

    /**
     * @var array
     */
    protected $messages;

    /**
     * @var DirectoryList
     */
    protected $dir;

    /**
     * @var bool
     */
    protected $hasError = false;

    /**
     * AbstractImportExport constructor.
     * @param Ftp $ftp
     * @param Sftp $sftp
     * @param ScopeConfigInterface $scopeConfig
     * @param LogRepositoryInterface $logRepository
     * @param \PH2M\Logistic\Model\LogFactory $logFactory
     * @param Connectiontype $connectiontypeSource
     */
    public function __construct(
        Ftp $ftp,
        Sftp $sftp,
        File $local,
        ScopeConfigInterface $scopeConfig,
        LogRepositoryInterface $logRepository,
        LogFactory $logFactory,
        Connectiontype $connectiontypeSource,
        DirectoryList $dir
    ) {
        $this->ftp                  = $ftp;
        $this->sftp                 = $sftp;
        $this->local                = $local;
        $this->scopeConfig          = $scopeConfig;
        $this->logFactory           = $logFactory;
        $this->logRepository        = $logRepository;
        $this->connectionTypeSource = $connectiontypeSource;
        $this->dir                  = $dir;

        $this->fieldSeparator = $this->_getConfig('general', 'field_separator');
        $this->fieldEnclosure = $this->_getConfig('general', 'field_enclosure');
    }

    /**
     * @param $group
     * @param $field
     * @return string
     */
    protected function _getConfig($group, $field)
    {
        return $this->scopeConfig->getValue('logistic/' . $group . '/' . $field, ScopeInterface::SCOPE_STORE);
    }

    /**
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    protected function _initConnection()
    {
        $this->_setConnection();

        if ($this->_getConfig('connection', 'type') !== Connectiontype::CONNECTION_TYPE_LOCAL){
            $host = $this->_getConfig('connection', 'host');
            if ($configPort = $this->_getConfig('connection', 'port')) {
                $host .= ':' . $configPort;
            }

            $params = [
                'host'      => $host,
                // FTP needs user and SFTP needs username so it's easier to send both
                'user'      => $this->_getConfig('connection', 'username'),
                'username'  => $this->_getConfig('connection', 'username'),
                'password'  => $this->_getConfig('connection', 'password'),
            ];

            if ($this->_getConfig('connection', 'passive')) {
                $params['passive'] = true;
            }

            $this->connection->open($params);
        } else {
            $this->connection->open([
                'path' => $this->dir->getPath('var')
            ]);
        }
    }

    /**
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    protected function _setConnection()
    {
        $connectionType = $this->_getConfig('connection', 'type');

        $this->connectionTypeSource->validateType($connectionType);

        $this->connection = $this->$connectionType;
    }
}
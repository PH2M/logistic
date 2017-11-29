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
namespace PH2M\Logistic\Model\Import;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Filesystem\Io\Ftp;
use Magento\Framework\Filesystem\Io\Sftp;
use Magento\Store\Model\ScopeInterface;
use PH2M\Logistic\Model\Config\Source\Connectiontype;

/**
 * Class ImportAbstract
 * @package PH2M\Logistic\Model\Import
 */
abstract class AbstractImport
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
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var Connectiontype
     */
    protected $connectionTypeSource;

    /**
     * AbstractImport constructor.
     * @param Ftp $ftp
     * @param Sftp $sftp
     * @param ScopeConfigInterface $scopeConfig
     * @param Connectiontype $connectiontypeSource
     */
    public function __construct(
        Ftp $ftp,
        Sftp $sftp,
        ScopeConfigInterface $scopeConfig,
        Connectiontype $connectiontypeSource
    ) {
        $this->ftp                  = $ftp;
        $this->sftp                 = $sftp;
        $this->scopeConfig          = $scopeConfig;
        $this->connectionTypeSource = $connectiontypeSource;
    }

    public function process()
    {
        $this->_downloadFiles();
        $this->_importDownloadedFiles();
        $this->_reportResult();
    }

    /**
     * - Connect to distant server (FTP or SFTP)
     * - Retrieve the matching files and download them to var/logistic folder
     *
     * @throws NotFoundException
     */
    protected function _downloadFiles()
    {
        $connection = $this->_getConnection();
        $host       = $this->_getConfig('connection', 'host');

        if ($configPort = $this->_getConfig('connection', 'port')) {
            $host .= ':' . $configPort;
        }

        $connection->open([
            'host'      => $host,
            'username'  => $this->_getConfig('connection', 'username'),
            'password'  => $this->_getConfig('connection', 'password')
        ]);

        if (!$connection->cd($this->_getConfig('import', $this->code . '_path'))) {
            throw new NotFoundException(__('Import %1 path does not exist', $this->code));
        }

        $files = $this->_getFilesList($connection);

        $this->_readFiles($connection, $files);

        $connection->close();
    }

    /**
     * @return Ftp|Sftp
     */
    protected function _getConnection()
    {
        $connectionType = $this->_getConfig('connection', 'type');
        $this->connectionTypeSource->validateType($connectionType);

        return $this->$connectionType;
    }

    /**
     * @param Ftp|Sftp $connection
     * @return array
     */
    protected function _getFilesList($connection)
    {
        $files = $connection->rawls();

        $filePattern = $this->_getConfig('import', $this->code . '_file_pattern');

        return array_keys(array_filter($files, function($fileDetails, $fileName) use ($filePattern) {
            // It must be a file (type 1) and match the config pattern
            return $fileDetails['type'] == 1 && preg_match($filePattern, $fileName);
        }, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * @param Ftp|Sftp $connection
     * @param array $files
     */
    protected function _readFiles($connection, $files)
    {
        if (!count($files)) {
            return;
        }

        $pathToSaveFiles = BP . DIRECTORY_SEPARATOR . DirectoryList::VAR_DIR . DIRECTORY_SEPARATOR . 'logistic' . DIRECTORY_SEPARATOR . $this->code;

        if (!is_dir($pathToSaveFiles)) {
            mkdir($pathToSaveFiles, 0777, true);
        }

        foreach ($files as $file) {
            $connection->read($file, $pathToSaveFiles . DIRECTORY_SEPARATOR . $file);
        }
    }

    protected function _importDownloadedFiles()
    {

    }

    protected function _reportResult()
    {

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
}
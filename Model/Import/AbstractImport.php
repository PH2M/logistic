<?php
/**
 * 2011-2017 PH2M
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0) that is available
 * through the world-wide-web at this URL: http://www.opensource.org/licenses/OSL-3.0
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to contact@ph2m.com so we can send you a copy immediately.
 *
 * @author PH2M - contact@ph2m.com
 * @copyright 2001-2017 PH2M
 * @license http://www.opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */
namespace PH2M\Logistic\Model\Import;

use FireGento\FastSimpleImport\Model\ImporterFactory;
use Magento\Framework\App\Filesystem\DirectoryList as FsDirectoryList;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\Filesystem\DriverPool;
use Magento\Framework\Filesystem\Io\Ftp;
use Magento\Framework\Filesystem\Io\Sftp;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Filesystem\File\ReadFactory;
use PH2M\Logistic\Model\AbstractImportExport;
use PH2M\Logistic\Model\Config\Source\Connectiontype;
use PH2M\Logistic\Model\Log;
use PH2M\Logistic\Model\LogFactory;
use PH2M\Logistic\Api\LogRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Filesystem\DirectoryList;

/**
 * Class ImportAbstract
 * @package PH2M\Logistic\Model\Import
 */
abstract class AbstractImport extends AbstractImportExport
{
    /**
     * @var array
     */
    protected $columnsToIgnore = [];

    /**
     * @var array
     */
    protected $columnsToRename = [];

    /**
     * @var array
     */
    protected $columnsFixedValues = [];

    /**
     * @var ReadFactory
     */
    protected $fileReaderFactory;

    /**
     * @var array
     */
    protected $filesToImport = [];

    /**
     * @var ImporterFactory
     */
    protected $importerFactory;

    /**
     * @var LogFactory
     */
    protected $logFactory;

    /**
     * @var LogRepositoryInterface
     */
    protected $logRepository;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var array
     */
    protected $websitesCodes = [];

    /**
     * @var array
     */
    protected $storesCodes = [];

    /**
     * @var string
     */
    protected $entityCode = 'catalog_product';

    /**
     * AbstractImport constructor.
     * @param Ftp $ftp
     * @param Sftp $sftp
     * @param ReadFactory $fileReaderFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param Connectiontype $connectiontypeSource
     * @param ImporterFactory $importerFactory
     * @param LogFactory $logFactory
     * @param LogRepositoryInterface $logRepository
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ReadFactory $fileReaderFactory,
        ImporterFactory $importerFactory,
        LogFactory $logFactory,
        LogRepositoryInterface $logRepository,
        StoreManagerInterface $storeManager,
        Ftp $ftp,
        Sftp $sftp,
        File $local,
        ScopeConfigInterface $scopeConfig,
        Connectiontype $connectiontypeSource,
        DirectoryList $dir
    ) {
        $this->fileReaderFactory    = $fileReaderFactory;
        $this->importerFactory      = $importerFactory;
        $this->storeManager         = $storeManager;

        $this->messages             = [];

        parent::__construct($ftp, $sftp, $local, $scopeConfig, $logRepository, $logFactory, $connectiontypeSource, $dir);
    }

    /**
     * @throws FileSystemException
     * @throws NotFoundException
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    public function process()
    {
        $this->_downloadFiles();
        $this->_importDownloadedFiles();
        $this->_reportResult();
    }

    /**
     * @throws NotFoundException
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    protected function _initConnection()
    {
        parent::_initConnection();

        if ($this->_getConfig('connection', 'type') == Connectiontype::CONNECTION_TYPE_LOCAL){
            $path = $this->dir->getPath('var') . $this->_getConfig('import', $this->code . '_path');
        } else {
            $path = $this->_getConfig('import', $this->code . '_path');
        }

        if (!$this->connection->cd($path)) {
            throw new NotFoundException(__('Import %1 path does not exist', $this->code));
        }
    }

    /**
     * - Connect to distant server (FTP or SFTP)
     * - Retrieve the matching files and download them to var/logistic folder
     *
     * @throws FileSystemException
     * @throws NotFoundException
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    protected function _downloadFiles()
    {
        $this->_initConnection();
        $files = $this->_getFilesList();
        $this->_readFiles($files);
    }

    /**
     * @return array
     */
    protected function _getFilesList()
    {
        $filePattern = $this->_getConfig('import', $this->code . '_file_pattern');
        $files = $this->connection->ls();

        return array_map(function($fileDetails) {
            return $fileDetails['text'];
            },
            array_filter($files, function($fileDetails) use ($filePattern) {
                return preg_match($filePattern, $fileDetails['text']);
            })
        );
    }

    /**
     * @param array $files
     * @throws FileSystemException
     */
    protected function _readFiles($files)
    {
        if (!count($files)) {
            return;
        }

        $pathToSaveFiles = BP . DIRECTORY_SEPARATOR . FsDirectoryList::VAR_DIR . DIRECTORY_SEPARATOR . 'logistic' . DIRECTORY_SEPARATOR . $this->code;

        if (!is_dir($pathToSaveFiles)) {
            mkdir($pathToSaveFiles, 0777, true);
        }

        foreach ($files as $file) {
            $filePath = $pathToSaveFiles . DIRECTORY_SEPARATOR . $file;
            if ($this->connection->read($file, $filePath)) {
                $this->filesToImport[] = $filePath;
            } else {
                throw new FileSystemException(__('Error while save file to %1', $filePath));
            }
        }
    }

    /**
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    protected function _importDownloadedFiles()
    {
        if (count($this->filesToImport)) {
            foreach ($this->filesToImport as $fileToImport) {
                $this->_importFile($fileToImport);
            }
        }

        $this->connection->close();
    }

    /**
     * @param $fileToImport
     */
    protected function _beforeImportFile($fileToImport)
    {
        
    }

    /**
     * @param $fileToImport
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    protected function _importFile($fileToImport)
    {
        $this->_beforeImportFile($fileToImport);
        $start = microtime(true);

        /** @var Log $log */
        $log = $this->logFactory->create();
        $log->setMessage('Import file ' . $fileToImport)
            ->setEntityType($this->code);
        $this->logRepository->save($log);

        /** @var \Magento\Framework\Filesystem\File\Read $fileReader */
        $fileReader     = $this->fileReaderFactory->create($fileToImport, DriverPool::FILE);
        $fileName       = explode('/', $fileToImport);
        $fileName       = end($fileName);

        $header         = [];
        $isHeader       = true;
        $dataToImport   = [];

        while ($data = $fileReader->readCsv(0, $this->fieldSeparator, $this->fieldEnclosure)) {
            if ($isHeader) {
                $header = $this->_renameHeaderColumns($data);
                $isHeader = false;
                continue;
            }

            $lineData = [];

            if (trim($data[0]) === '') {
                continue;
            }

            foreach ($data as $index => $value) {
                if (!array_key_exists($index, $header)) {
                    continue;
                }
                if (in_array($header[$index], $this->columnsToIgnore)) {
                    continue;
                }
                $lineData[trim($header[$index])] = trim($value);
            }

            $lineData = $this->_addFixedValues($lineData);
            $lineData = $this->_formatLineData($lineData);

            if ($lineData) {
                $dataToImport[] = $lineData;
            }
        }

        if (count($dataToImport)) {
            $result = $this->_launchImporter($dataToImport);

            if ($result['success']) {
                $end    = microtime(true);
                $time   = $end - $start;

                $this->messages[] = $fileName . ': ' . count($dataToImport) . ' lines imported in ' . number_format($time, 3) . ' seconds';
            } else {
                if (isset($result['message'])) {
                    $this->messages[] = $fileName . ': ERROR: ' . $result['message'];
                } else {
                    $this->messages[] = $fileName . ': Unknown error.';
                }
            }
        }

        $this->_moveFileToArchives($fileName);
    }

    /**
     * @param array $dataToImport
     * @return array
     */
    protected function _launchImporter($dataToImport)
    {
        $result = [];

        try {
            /** @var \FireGento\FastSimpleImport\Model\Importer $importer */
            $importer = $this->importerFactory->create();

            $importer->setEntityCode($this->entityCode);

            $dataToImport = $this->_beforeImportData($dataToImport);
            $importer->processImport($dataToImport);

            if ($importer->getValidationResult()) {
                $result['success'] = true;
            } else {
                $this->hasError = true;
                $result['success'] = false;
                $result['message'] = $importer->getLogTrace();
            }
            $result['success'] = $importer->getValidationResult();
        } catch (\Exception $e) {
            $this->hasError = true;
            $result['success'] = false;
            $result['message'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Override $this->columnsToRename to rename some header columns to real product attributes codes
     *
     * @param array $header
     * @return array
     */
    protected function _renameHeaderColumns(array $header)
    {
        if (count($this->columnsToRename)) {
            foreach ($header as $key => $headerColumnName) {
                if (isset($this->columnsToRename[$headerColumnName])) {
                    $header[$key] = $this->columnsToRename[$headerColumnName];
                }
            }
        }
        return $header;
    }

    /**
     * Override this function to format a product data array
     *
     * @param array $lineData
     * @return array
     */
    protected function _formatLineData(array $lineData)
    {
        return $lineData;
    }

    /**
     * @param array $lineData
     * @return array
     */
    protected function _addFixedValues(array $lineData)
    {
        return $lineData + $this->columnsFixedValues;
    }

    /**
     * Override this function to format data to import before importing it
     *
     * @param $dataToImport
     * @return array
     */
    protected function _beforeImportData(array $dataToImport)
    {
        return $dataToImport;
    }

    /**
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    protected function _reportResult()
    {
        if (count($this->messages)) {
            /** @var Log $log */
            $log = $this->logFactory->create();

            if ($this->hasError) {
                $log->setStatus(Log::STATUS_ERROR);
            } else {
                $log->setStatus(Log::STATUS_SUCCESS);
            }

            $log->setMessage(implode(PHP_EOL, $this->messages))
                ->setEntityType($this->code);

            $this->logRepository->save($log);
        }
    }

    /**
     * @return array
     */
    protected function _getWebsitesCodes()
    {
        if (empty($this->websitesCodes)) {
            $websites = $this->storeManager->getWebsites();

            foreach ($websites as $website) {
                $this->websitesCodes[] = $website->getCode();
            }
        }

        return $this->websitesCodes;
    }

    /**
     * @return array
     */
    protected function _getStoresCodes()
    {
        if (empty($this->storesCodes)) {
            $stores = $this->storeManager->getStores();

            foreach ($stores as $store) {
                $this->storesCodes[] = $store->getCode();
            }
        }

        return $this->storesCodes;
    }

    /**
     * @param $file
     * @throws FileSystemException
     */
    protected function _moveFileToArchives($file)
    {
        if ($this->_getConfig('connection', 'type') == Connectiontype::CONNECTION_TYPE_LOCAL){
            $archivesPath = $this->_getConfig('import', $this->code . '_archive_path') ? $this->dir->getPath('var') . $this->_getConfig('import', $this->code . '_archive_path') : '';
        } else {
            $archivesPath = $this->_getConfig('import', $this->code . '_archive_path');
        }

        if ($archivesPath && !$this->connection->mv($file, $archivesPath . '/' . $file . '-' . time())) {
            $this->hasError = true;
            $this->messages[] = 'Error during moving file ' . $this->_getConfig('import', $this->code . '_path') . '/' . $file . ' to the archives';
        }
    }
}

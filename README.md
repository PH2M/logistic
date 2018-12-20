PH2M_Logistic
-------
Manage your imports / exports.

Requirements
------------
Magento >= 2.1.0

Installation
------------
```
composer require ph2m/logistic
bin/magento module:enable FireGento_FastSimpleImport FireGento_ExtendedImport PH2M_Logistic
bin/magento setup:upgrade
```

Console commands
----------------
You can launch the imports by typing the following commands:
```
# Products import
bin/magento logistic:import:products

# Stocks import
bin/magento logistic:import:stocks
```

Launch tests
------------
```
vendor/phpunit/phpunit/phpunit -c dev/tests/unit/phpunit.xml.dist vendor/ph2m/logistic
```

To do list
----------
- [ ] Complete unit tests
- [ ] Add WS connection type

Add another import
-------------------------
The following steps are for a new import class but they are the same for an export one. Just use export class/folders instead.

- Create a class which extends the `PH2M\Logistic\Model\Import\AbstractImport` class
- In this class, add a `code` variable
- Add in your `system.xml` these configurations (replace `<code>` by your `code` variable value):
    - `<code>_enable`: a `select` with a `Magento\Config\Model\Config\Source\Yesno` source model
    - `<code>_path`: a `text` field
    - `<code>_file_pattern`: a `text` field
    - `<code>_archive_path`: a `text` field
- If necessary, override the `columnsToIgnore` variable to ignore some columns
- If necessary, override the `columnsToRename` variable to rename some header columns to real product attributes codes:
    ```
    /**
     * @var array
     */
    protected $columnsToRename = [
        'columnFromFile' => 'newColumnName'
    ]; 
    ```
- If necessary, override the `columnsFixedValue` variable to add some fixed values (attribute set if it's not defined in your CSV file for example)

Add a custom object import
--------------------------
If you want to import a custom object (stores from a store locator for example), override the `_launchImporter` method in your import class.
This method should return an array which has a `success` and a `message` (in case of error) value.

Add another export
------------------
- Create a class which extends the `PH2M\Logistic\Model\Export\AbstractExport\` class
- In this class, add a `code` variable
- If you don't want to create a file for each exported object, set the `createAFileForEachObject` variable to false 
- Override `_getFileName` function to set the export file name
- Override `_initObjectsToExport` function to return the objects to export
- Add in your `system.xml` these configurations (replace `<code>` by your `code` variable value):
    - `<code>_enable`: a `select` with a `Magento\Config\Model\Config\Source\Yesno` source model
    - `<code>_path`: a `text` field
- XML files are not supported at the moment, if you want to export as XML you have to override the `_exportObjects` function and send an empty header to `_createAndSendFile`

Local import
-------
Local import must be placed in `/var` directory. You can next setup your import and archive paths like in the distant imports.

Licence
-------
[GNU General Public License, version 3 (GPLv3)](http://opensource.org/licenses/gpl-3.0)

Troubleshooting
---------------
```
This file does not contain any data.
```
I have seen this issue because I was trying to import a product attribute which had a code in camel case, ie `MyAttribute`. Replace it by `my_attribute`.
It can also happen if your data has a bad format, ie you're trying to import an array as value.

Special thanks
--------------
Special thanks to Firegento and all contributors to the [FastSimpleImport extension](https://github.com/firegento/FireGento_FastSimpleImport2)!

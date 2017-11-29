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
bin/magento module:enable FireGento_FastSimpleImport
bin/magento module:enable PH2M_Logistic
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

Add another import/export
-------------------------
The following steps are for a new import class but they are the same for an export one. Just use export class/folders instead.

- Create a class which extends the `PH2M\Logistic\Model\Import\AbstractImport` class
- In this class, add a `code` parameter
- Add in your `system.xml` three configurations (replace `<code>` by your `code` parameter value):
    - `<code>_enable`: a `select` with a `Magento\Config\Model\Config\Source\Yesno` source model
    - `<code>_path`: a `text` field
    - `<code>_file_pattern`: a `text` field
    - `<code>_archive_path`: a `text` field. Note that this field is not required for an export

Licence
-------
[GNU General Public License, version 3 (GPLv3)](http://opensource.org/licenses/gpl-3.0)

Special thanks
--------------
Special thanks to Firegento and all contributors to the [FastSimpleImport extension](https://github.com/firegento/FireGento_FastSimpleImport2)!

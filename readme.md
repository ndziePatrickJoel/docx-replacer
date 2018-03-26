## PHP .docx replacer

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Total Downloads][ico-downloads]][link-downloads]

### Replace your variables to text or even to images in .docx files

### Install

Require this package with composer using the following command:

```bash
composer require irebega/docx-replacer
```

### Text to text replace

This code will replace **$search** to **$replace** in **$pathToDocx** file

```php
$docx = new IRebega\Docx($pathToDocx);

$docx->replaceText($search, $replace);
```

### Text to multiple text replace in one

This code will replace every key in **$fromToArray** to his corresponding value and apply it to **$pathToDocx** file

```php
$docx = new IRebega\Docx($pathToDocx);

$fromToArray = array('key1' => 'value1', 'key2' => 'value2', ...);

$docx->replaceTexts($fromToArray);
```


### Text to image replace

This code will replace text **$search** to image that are located in **$path** in **$pathToDocx** file

```php
$docx = new IRebega\DocxReplacer($pathToDocx);

$docx->replaceTextToImage($search, $path);
```
### License

PHP .docx replacer is open-sourced software licensed under the [MIT license](http://opensource.org/licenses/MIT)


[ico-version]: https://img.shields.io/packagist/v/irebega/docx-replacer.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/irebega/docx-replacer.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/irebega/docx-replacer
[link-downloads]: https://packagist.org/packages/irebega/docx-replacer
[link-author]: https://github.com/igorrebega

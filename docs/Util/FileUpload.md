# FileUpload component

This component class is designed to help upload files in Contao with a custom configuration. 


## Usage

The example code is below:

```php
use Codefog\HasteBundle\Util\FileUpload

$uploader = new FileUpload('my_uploads');
$uploader->setExtensions(['pdf']);
$uploader->setMaxFileSize(2000000);
$uploader->uploadTo('uploads_folder');

if ($uploader->hasError()) {
    // … error …
}
```

The component is used in [terminal42/contao-fineuploader](https://github.com/terminal42/contao-fineuploader) inside
the `\Terminal42\FineUploaderBundle\Uploader` class. 

# FileUploadNormalizer

The problem the `FileUploadNormalizer` tries to tackle is that Contao's file uploads in the form generator (`Form.php`) 
accesses file uploads from the widget itself and there is no defined API. The built-in upload form field generates a
typical PHP upload array. Some form field widgets return a Contao Dbafs UUID, others just a file path and some even
return multiple values. It's a mess.

It is designed to be used with the `processFormData` hook specifically.

## Usage

```php

use Codefog\HasteBundle\FileUploadNormalizer;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Form;
use Contao\Widget;

#[AsHook('prepareFormData')]
class PrepareFomDataListener
{
    public function __construct(private readonly FileUploadNormalizer $fileUploadNormalizer)
    {
    }

    /**
     * @param array<Widget> $fields
     */
    public function __invoke(array $submitted, array $labels, array $fields, Form $form, array $files): void
    {
        // You now have an array of normalized files.
        $normalizedFiles = $this->fileUploadNormalizer->normalize($files);
    }
}
```

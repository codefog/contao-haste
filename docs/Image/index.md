# Image

Haste provides helpers for common image handling. Especially gallery functionality
in Contao's `ContentGallery` is very cumbersome and can hardly ever be reused.

### getForGalleryTemplate($uuids, array $options)

This returns an array for a gallery based on options you pass. There is a huge
array of options you can use, giving you maximum flexibility.

First argument is an array of file UUIDs or a serialized string of UUIDs.

The valid values for `$options` are all valid ones for the `prepareImage()` and
`sortImages()` methods.

This is very useful for using it in a template like so:

```php
<?php
$this->insert('gallery_default', \Haste\Image\Image::getForGalleryTemplate(
    $this->gallery_field,
    [
        'sortBy' => \Haste\Image\Image::SORT_CUSTOM,
        'orderSRC' => $this->gallery_order_field,
        'size' => 42
    ]
));
```

### Method: findImages($uuids, array $options)

This method finds images and enriches them with meta data etc. optionally allowing
to sort them.

First argument is an array of file UUIDs or a serialized string of UUIDs.

The valid values for `$options` are all valid ones for the `prepareImage()` method.

### Method: prepareImage(FilesModel $fileModel, array $options)

This method allows you to prepare an image of which you already have the `FilesModel`
instance and enrich it with meta data and render them using responsive image
settings etc.

The valid values for `$options` are:

* `language` - needed for the file meta data, if you don't provide this, it will try to load it from the current page object
* `size` - either an `integer` containing the ID of the responsive image setting or an array*.
* `fullsize` - a `boolean` specifying whether you would like to allow the fullsize mode in a gallery
* `maxWidth` - an `integer`speciyfing the maximum width of an image
* `lightboxId` - a `string` containing a lightbox id to group images by lightbox id

\* If `size` is an array. The first value represents the `width`, second the `height` and
third the `crop_mode`. Valid crop modes (in the core, can be extended by third
party modules) are:

* `proprotional`
* `box`
* `left_top`
* `center_top`
* `right_top`
* `left_center`
* `center_center`
* `right_center`
* `left_bottom`
* `center_bottom`
* `right_bottom`

### Method: sortImages(array &$images, $sortBy = 'name_asc', $options = [])

Sorts an array of images in the format of the `findImages` method.

Note: This method does **NOT** return anything. Instead it modifies the `$images`
array you pass as first argument.

The default sorting algorithm is `\Haste\Image\Image::SORT_NAME_ASC` and no
options, so you can pass the first argument only if you like.

The valid values for `$sortBy` are:

 * `\Haste\Image\Image::SORT_NAME_ASC` (sort by name, ascending)
 * `\Haste\Image\Image::SORT_NAME_DESC` (sort by name, descending)
 * `\Haste\Image\Image::SORT_DATE_ASC` (sort by date, ascending)
 * `\Haste\Image\Image::SORT_DATE_DESC` (sort by date, descending)
 * `\Haste\Image\Image::SORT_CUSTOM` | orders the files by another array of file UUIDs which **must** be passed using the orderSRC options key)
 * `\Haste\Image\Image::SORT_RANDOM` (sort randomly)

The valid values for the `$options` array are:

* `orderSRC` - contains an array of file UUIDs to sort by (needed for sorting mode `\Haste\Image\Image::SORT_CUSTOM`)

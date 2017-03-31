# Haste template helpers

Haste provides helpers for common stuff that needs to be done in templates.
All of Haste's template helpers can be accessed in **every** template via
the `$this->hasteTemplateHelpers` helper object so they are grouped.
We'll document the methods in the following section, each of the methods can thus
be called by using `$this->hasteTemplateHelpers-><method>(<arguments ...>)`.

## Image handling

### getGallery(array $options)

This outputs a complete gallery based on options you pass. There is a huge
array of options you can use, giving you maximum flexibility.

The valid values for `$options` are:

* `template` (by default it's `gallery_default` but you can specify your own)
* plus all options of the methods `findImages`, `prepareImage` and `sortImages`

### Method: findImages(array $options)

This method finds images and enriches them with meta data etc. optionally allowing
to sort them.

The valid values for `$options` are:

* `multiSRC` (an array containing file UUIDs or folder UUIDs (can even be serialized))
* `singleSRC` (a string containing a file UUID or folder UUID)
* plus all options of the methods `prepareImage` and `sortImages`

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

The default sorting algorithm is `name_asc` and no options, so you can pass the
first argument only if you like.

The valid values for `$sortBy` are:

* `name_asc` - orders by file name ascending
* `name_desc` - orders by file name descending
* `date_asc` - orders by the file's last modified date ascending
* `date_desc` - orders by the file's last modified date descending
* `random` - orders the files randomly
* `custom` - orders the files by another array of file UUIDs which **must** be passed using the `$options['orderSRC']` option

The valid values for the `$options` array are:

* `orderSRC` - contains an array of file UUIDs to sort by (needed for sorting mode `custom`)

### Examples

A typical example would be the following: You added a new field `gallery` to the DCA which
allows to select multiple files or folders and now you would like to display a
gallery for those images. You need them to resize with your responsive image setting
ID 42 and you would like to order them by the custom order you chose in the back
end for which there is an order field called `gallery_order` containing the UUIDs
in correct order. Your template is now nice an short because it contains only this:

```
<div class="my-gallery">
<?= $this->hasteTemplateHelpers->getGallery([
 	'multiSRC' => $this->gallery,
 	'sortBy' => 'custom',
 	'orderSRC' => $this->gallery_order,
 	'size' => 42
 ]);
</div>

```

Neat, huh? :-)

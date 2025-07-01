# Formatter component

This component is designed to help format the common values in Contao. 


## Usage

See the examples below:

```php
use Codefog\HasteBundle\Formatter;

// Format date/time/datim
$this->formatter->date($timestamp);
$this->formatter->time($timestamp);
$this->formatter->datim($timestamp);

// Display the tl_news.headline label
$this->formatter->dcaLabel('tl_news', 'headline');

// Display the tl_news.source formatted value
$this->formatter->dcaValue('tl_news', 'source', $newsModel->source);
```


## Twig Usage

You can also use the formatting helper inside Twig templates.

```twig
<table>
<thead>
    <tr>
        <th>{{ dca_label('tl_news', 'headline') }}</th>
        <th>{{ dca_label('tl_news', 'published') }}</th>
    </tr>
</thead>
<tbody>
    {% for item in news %}
        <tr>
            <td>{{ dca_value('tl_news', 'headline', item.headline) }}</td>
            <td>{{ dca_value('tl_news', 'published', item.published) }}</td>
        </tr>
    {% endfor %}
</tbody>
</table>
```

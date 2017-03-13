<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element as RenderElement;
use Drupal\webform\Utility\WebformElementHelper;
use Drupal\webform\Utility\WebformOptionsHelper;

/**
 * Provides a trait for composite elements.
 */
trait WebformCompositeTrait {

  /**
   * {@inheritdoc}
   */
  public function isInput(array $element) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getRelatedTypes(array $element) {
    return [];
  }

  /**
   * Get composite elements.
   *
   * @return array
   *   An array of composite elements.
   */
  abstract protected function getCompositeElements();

  /**
   * Get initialized composite element.
   *
   * @param array &$element
   *   A composite element.
   *
   * @return array
   *   The initialized composite test element.
   */
  abstract protected function getInitializedCompositeElement(array &$element);

  /**
   * {@inheritdoc}
   */
  public function getTableColumn(array $element) {
    $key = $element['#webform_key'];
    $title = $element['#title'] ?: $key;
    $is_title_displayed = WebformElementHelper::isTitleDisplayed($element);

    // Get the main composite element, which can't be sorted.
    $columns = parent::getTableColumn($element);
    $columns['element__' . $key]['sort'] = FALSE;

    // Get individual composite elements.
    $composite_elements = $this->getInitializedCompositeElement($element);
    foreach (RenderElement::children($composite_elements) as $composite_key) {
      $composite_element = $composite_elements[$composite_key];
      // Make sure the composite element is visible.
      $access_key = '#' . $composite_key . '__access';
      if (isset($element[$access_key]) && $element[$access_key] === FALSE) {
        continue;
      }

      // Add reference to initialized composite element so that it can be
      // used by ::formatTableColumn().
      $columns['element__' . $key . '__' . $composite_key] = [
        'title' => ($is_title_displayed ? $title . ': ' : '') . (!empty($composite_element['#title']) ? $composite_element['#title'] : $composite_key),
        'sort' => TRUE,
        'default' => FALSE,
        'key' => $key,
        'element' => $element,
        'property_name' => $composite_key,
        'composite_key' => $composite_key,
        'composite_element' => $composite_element,
        'plugin' => $this,
      ];
    }
    return $columns;
  }

  /**
   * {@inheritdoc}
   */
  public function formatTableColumn(array $element, $value, array $options = []) {
    if (isset($options['composite_key']) && isset($options['composite_element'])) {
      $composite_key = $options['composite_key'];
      $composite_element = $options['composite_element'];
      $composite_value = $value[$composite_key];
      $composite_options = [];

      return $this->elementManager->invokeMethod('formatHtml', $composite_element, $composite_value, $composite_options);
    }
    else {
      return $this->formatHtml($element, $value);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getElementSelectorOptions(array $element) {
    if ($this->hasMultipleValues($element)) {
      return [];
    }

    $title = $this->getAdminLabel($element) . ' [' . $this->getPluginLabel() . ']';
    $name = $element['#webform_key'];

    $selectors = [];
    $composite_elements = $this->getInitializedCompositeElement($element);
    foreach ($composite_elements as $composite_key => $composite_element) {
      $has_access = (!isset($composite_elements['#access']) || $composite_elements['#access']);
      if ($has_access && isset($composite_element['#type'])) {
        $element_handler = $this->elementManager->getElementInstance($composite_element);
        $composite_title = (isset($composite_element['#title'])) ? $composite_element['#title'] : $composite_key;

        switch ($composite_element['#type']) {
          case 'label':
          case 'webform_message':
            break;

          case 'webform_select_other':
            $selectors[":input[name=\"{$name}[{$composite_key}][select]\"]"] = $composite_title . ' [' . $this->t('Select') . ']';
            $selectors[":input[name=\"{$name}[{$composite_key}][other]\"]"] = $composite_title . ' [' . $this->t('Textfield') . ']';
            break;

          default:
            $selectors[":input[name=\"{$name}[{$composite_key}]\"]"] = $composite_title . ' [' . $element_handler->getPluginLabel() . ']';
            break;
        }
      }
    }
    return [$title => $selectors];
  }

  /**
   * {@inheritdoc}
   */
  public function formatHtmlItem(array &$element, $value, array $options = []) {
    // Return empty value.
    if (empty($value) || empty(array_filter($value))) {
      return '';
    }

    $format = $this->getItemFormat($element);
    switch ($format) {
      case 'list':
        $items = [];
        $composite_elements = $this->getInitializedCompositeElement($element);
        foreach (RenderElement::children($composite_elements) as $composite_key) {
          $composite_element = $composite_elements[$composite_key];
          $composite_title = (isset($composite_element['#title'])) ? $composite_element['#title'] : $composite_key;
          $composite_value = (isset($value[$composite_key])) ? $value[$composite_key] : '';
          if ($composite_value !== '') {
            $items[$composite_key] = ['#markup' => "<b>$composite_title:</b> $composite_value"];
          }
        }
        return [
          '#theme' => 'item_list',
          '#items' => $items,
        ];

      case 'raw':
        $items = [];
        $composite_elements = $this->getInitializedCompositeElement($element);
        foreach (RenderElement::children($composite_elements) as $composite_key) {
          $composite_value = (isset($value[$composite_key])) ? $value[$composite_key] : '';
          if ($composite_value !== '') {
            $items[$composite_key] = ['#markup' => "<b>$composite_key:</b> $composite_value"];
          }
        }
        return [
          '#theme' => 'item_list',
          '#items' => $items,
        ];

      default:
        $lines = $this->formatHtmlItemValue($element, $value);
        foreach ($lines as $key => $line) {
          if (is_string($line)) {
            if ($key == 'email') {
              $lines[$key] = [
                '#type' => 'link',
                '#title' => $line,
                '#url' => \Drupal::pathValidator()->getUrlIfValid('mailto:' . $line),
              ];
            }
            else {
              $lines[$key] = ['#markup' => $line];
            }
          }
          $lines[$key]['#suffix'] = '<br/>';
        }
        return $lines;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getItemFormats() {
    return parent::getItemFormats() + [
        'list' => $this->t('List'),
      ];
  }

  /**
   * {@inheritdoc}
   */
  public function formatTextItem(array &$element, $value, array $options = []) {
    // Return empty value.
    if (empty($value) || (is_array($value) && empty(array_filter($value)))) {
      return '';
    }

    $format = $this->getItemFormat($element);
    switch ($format) {
      case 'list':
        $items = [];
        $composite_elements = $this->getInitializedCompositeElement($element);
        foreach (RenderElement::children($composite_elements) as $composite_key) {
          if (isset($value[$composite_key]) && $value[$composite_key] !== '') {
            $composite_value = $value[$composite_key];
            if (!empty($composite_elements[$composite_key]['#title'])) {
              $composite_title = $composite_elements[$composite_key]['#title'];
              $items[$composite_key] = "$composite_title: $composite_value";
            }
            else {
              $items[$composite_key] = $composite_value;

            }
          }
        }
        return implode(PHP_EOL, $items);

      case 'raw':
        $items = [];
        $composite_elements = $this->getInitializedCompositeElement($element);
        foreach (RenderElement::children($composite_elements) as $composite_key) {
          if (isset($value[$composite_key]) && $value[$composite_key] !== '') {
            $composite_value = $value[$composite_key];
            $items[$composite_key] = "$composite_key: $composite_value";
          }
        }
        return implode(PHP_EOL, $items);

      default:
        $lines = $this->formatTextItemValue($element, $value);
        return implode(PHP_EOL, $lines);
    }
  }

  /**
   * Format composite element value into lines of text.
   *
   * @param array $element
   *   A composite element.
   * @param array $value
   *   Composite element values.
   *
   * @return array
   *   Composite element values converted into lines of html.
   */
  protected function formatHtmlItemValue(array $element, array $value) {
    $items = [];
    $composite_elements = $this->getInitializedCompositeElement($element);
    foreach (RenderElement::children($composite_elements) as $composite_key) {
      if (isset($value[$composite_key]) && $value[$composite_key] != '') {
        $composite_element = $composite_elements[$composite_key];
        $composite_title = $composite_element['#title'];
        $composite_value = $value[$composite_key];
        $items[$composite_key] = "<b>$composite_title:</b> $composite_value";
      }
    }
    return $items;
  }

  /**
   * Format composite element value into lines of text.
   *
   * @param array $element
   *   A composite element.
   * @param array $value
   *   Composite element values.
   *
   * @return array
   *   Composite element values converted into lines of text.
   */
  protected function formatTextItemValue(array $element, array $value) {
    $items = [];
    $composite_elements = $this->getInitializedCompositeElement($element);
    foreach (RenderElement::children($composite_elements) as $composite_key) {
      if (isset($value[$composite_key]) && $value[$composite_key] != '') {
        $composite_element = $composite_elements[$composite_key];
        $composite_title = $composite_element['#title'];
        $composite_value = $value[$composite_key];
        $items[$composite_key] = "$composite_title: $composite_value";
      }
    }
    return $items;
  }

  /**
   * {@inheritdoc}
   */
  public function getItemsDefaultFormat() {
    return 'ul';
  }

  /**
   * {@inheritdoc}
   */
  public function getItemsFormats() {
    return [
      'ol' => $this->t('Ordered list'),
      'ul' => $this->t('Unordered list'),
      'hr' => $this->t('Horizontal rule'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getExportDefaultOptions() {
    return [
      'composite_element_item_format' => 'label',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportOptionsForm(array &$form, FormStateInterface $form_state, array $export_options) {
    parent::buildExportOptionsForm($form, $form_state, $export_options);
    if (isset($form['composite'])) {
      return;
    }

    $form['composite'] = [
      '#type' => 'details',
      '#title' => $this->t('Composite element options'),
      '#open' => TRUE,
      '#weight' => -10,
    ];
    $form['composite']['composite_element_item_format'] = [
      '#type' => 'radios',
      '#title' => $this->t('Composite element item format'),
      '#options' => [
        'label' => $this->t('Option labels, the human-readable value (label)'),
        'key' => $this->t('Option values, the raw value stored in the database (key)'),
      ],
      '#default_value' => $export_options['composite_element_item_format'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportHeader(array $element, array $options) {
    if (!empty($element['#multiple'])) {
      return parent::buildExportHeader($element, $options);
    }

    $composite_elements = $this->getInitializedCompositeElement($element);
    $header = [];
    foreach (RenderElement::children($composite_elements) as $composite_key) {
      $composite_element = $composite_elements[$composite_key];
      if (isset($composite_element['#access']) && $composite_element['#access'] === FALSE) {
        continue;
      }

      if ($options['header_format'] == 'label' && !empty($composite_element['#title'])) {
        $header[] = $composite_element['#title'];
      }
      else {
        $header[] = $composite_key;
      }
    }

    return $this->prefixExportHeader($header, $element, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function buildExportRecord(array $element, $value, array $export_options) {
    if (!empty($element['#multiple'])) {
      $element['#format'] = ($export_options['header_format'] == 'label') ? 'list' : 'raw';
      $export_options['multiple_delimiter'] = PHP_EOL . '---' . PHP_EOL;
      return parent::buildExportRecord($element, $value, $export_options);
    }

    $record = [];
    $composite_elements = $this->getInitializedCompositeElement($element);
    foreach (RenderElement::children($composite_elements) as $composite_key) {
      $composite_element = $composite_elements[$composite_key];
      if (isset($composite_element['#access']) && $composite_element['#access'] === FALSE) {
        continue;
      }

      if ($export_options['composite_element_item_format'] == 'label' && $composite_element['#type'] != 'textfield' && !empty($composite_element['#options'])) {
        $record[] = WebformOptionsHelper::getOptionText($value[$composite_key], $composite_element['#options']);
      }
      else {
        $record[] = (isset($value[$composite_key])) ? $value[$composite_key] : NULL;
      }
    }
    return $record;
  }

}

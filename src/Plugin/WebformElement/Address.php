<?php

namespace Drupal\webform\Plugin\WebformElement;

use Drupal\address\LabelHelper;
use Drupal\address\Plugin\Field\FieldType\AddressItem;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\webform\WebformElementBase;
use Drupal\webform\WebformInterface;

/**
 * Provides a 'address' element.
 *
 * @WebformElement(
 *   id = "address",
 *   label = @Translation("Address module"),
 *   description = @Translation("Provides advanced element for storing, validating and displaying international postal addresses."),
 *   category = @Translation("Advanced elements"),
 *   composite = TRUE,
 *   multiline = TRUE,
 * )
 *
 * @see \Drupal\address\Element\Address
 */
class Address extends WebformElementBase {

  use WebformCompositeTrait;

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties() {
    return parent::getDefaultProperties() + [
      'available_countries' => [],
      'used_fields' => [],
      'langcode_override' => '',
    ];
  }


  /**
   * Get composite elements.
   *
   * @return array
   *   An array of composite elements.
   */
  protected function getCompositeElements() {
    return [
      'given_name' => [
        '#title' => $this->t('Given name'),
        '#type' => 'textfield'
      ],
      'family_name' => [
        '#title' => $this->t('Family name'),
        '#type' => 'textfield'
      ],
      'organization' => [
        '#title' => $this->t('Organization'),
        '#type' => 'textfield'
      ],
      'address_line1' => [
        '#title' => $this->t('Address line 1'),
        '#type' => 'textfield'
      ],
      'address_line2' => [
        '#title' => $this->t('Address line 2'),
        '#type' => 'textfield'
      ],
      'postal_code' => [
        '#title' => $this->t('Postal code'),
        '#type' => 'textfield'
      ],
      'locality' => [
        '#title' => $this->t('Locality'),
        '#type' => 'textfield'
      ],
      'administrative_area' => [
        '#title' => $this->t('Administrative area'),
        '#type' => 'textfield'
      ],
      'country_code' => [
        '#title' => $this->t('Country code'),
        '#type' => 'textfield'
      ],
      'langcode' => [
        '#title' => $this->t('Language code'),
        '#type' => 'textfield'
      ],
    ];
  }

  /**
   * Get initialized composite element.
   *
   * @param array &$element
   *   A composite element.
   *
   * @return array
   *   The initialized composite test element.
   */
  protected function getInitializedCompositeElement(array &$element) {
    return $element + $this->getCompositeElements();
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $form['address'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Address settings'),
    ];

    /**************************************************************************/
    // Copied from: \Drupal\address\Plugin\Field\FieldType\AddressItem::fieldSettingsForm
    /**************************************************************************/

    $languages = \Drupal::languageManager()->getLanguages(LanguageInterface::STATE_ALL);
    $language_options = [];
    foreach ($languages as $langcode => $language) {
      if (!$language->isLocked()) {
        $language_options[$langcode] = $language->getName();
      }
    }

    $form['address']['available_countries'] = [
      '#type' => 'select',
      '#title' => $this->t('Available countries'),
      '#description' => $this->t('If no countries are selected, all countries will be available.'),
      '#options' => \Drupal::service('address.country_repository')->getList(),
      '#multiple' => TRUE,
      '#size' => 10,
    ];
    $form['address']['used_fields'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Used fields'),
      '#description' => $this->t('Note: an address used for postal purposes needs all of the above fields.'),
      '#options' => LabelHelper::getGenericFieldLabels(),
      '#required' => TRUE,
    ];
    $form['address']['langcode_override'] = [
      '#type' => 'select',
      '#title' => $this->t('Language override'),
      '#description' => $this->t('Ensures entered addresses are always formatted in the same language.'),
      '#options' => $language_options,
      '#empty_option' => $this->t('- No override -'),
      '#access' => \Drupal::languageManager()->isMultilingual(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getTestValues(array $element, WebformInterface $webform, array $options = []) {
    return [
      [
        'given_name' => 'John',
        'family_name' => 'Smith',
        'organization' => 'Google Inc.',
        'address_line1' => '1098 Alta Ave',
        'postal_code' => '94043',
        'locality' => 'Mountain View',
        'administrative_area' => 'CA',
        'country_code' => 'US',
        'langcode' => 'en',
      ],
    ];
  }

}

<?php

namespace Drupal\webform_test_element\Plugin\WebformElement;

use Drupal\Core\Form\FormState;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform_test_element\Element\WebformTestComposite as WebformTestCompositeElement;

/**
 * Provides a 'webform_test_composite' element.
 *
 * @WebformElement(
 *   id = "webform_test_composite",
 *   label = @Translation("Test composite element"),
 *   description = @Translation("Provides a ∑ebform composite element for testing."),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class WebformTestComposite extends WebformCompositeBase {

}

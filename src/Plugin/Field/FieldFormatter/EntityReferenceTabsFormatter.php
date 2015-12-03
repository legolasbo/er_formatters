<?php

/**
 * @file
 * contains \Drupal\er_formatters\Plugin\Field\FieldFormatter\EntityReferenceTabsFormatter
 */

namespace Drupal\er_formatters\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceEntityFormatter;
use Drupal\Core\Form\FormState;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\VerticalTabs;
use Drupal\field_group\Element\HorizontalTabs;

/**
 * Plugin implementation of the entity reference tabs formatter.
 *
 * @FieldFormatter(
 *   id = "entity_reference_tabs",
 *   label = @Translation("Tabs"),
 *   description = @Translation("Display the referenced entities as horizontal or vertical tabs."),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class EntityReferenceTabsFormatter extends EntityReferenceEntityFormatter {

  /**
   * Builds a renderable array for a field value.
   *
   * @param \Drupal\Core\Field\FieldItemListInterface $items
   *   The field values to be rendered.
   * @param string $langcode
   *   The language that should be used to render the field.
   *
   * @return array
   *   A renderable array for $items, as an array of child elements keyed by
   *   consecutive numeric indexes starting from 0.
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $type = $this->getSetting('type') . '_tabs';
    $element = [
      '#parents' => [$type],
      '#type' => $type,
      '#title' => '',
      '#theme_wrappers' => [$type],
      '#default_tab' => '',
    ];

    foreach ($this->getEntitiesToView($items, $langcode) as $delta => $entity) {
      $tab = [
        '#type' => 'details',
        '#title' => $entity->label(),
        '#description' => '',
      ];

      $view_builder = \Drupal::entityTypeManager()->getViewBuilder($entity->getEntityTypeId());
      $tab[] = $view_builder->view($entity, $this->getSetting('view_mode'), $langcode);
      $element[] = $tab;
    }

    $form_state = new FormState();
    if ($this->getSetting('type') == 'vertical') {
      $complete_form = [];
      $element = VerticalTabs::processVerticalTabs($element, $form_state, $complete_form);
      // Make sure the group has 1 child. This is needed to succeed at
      // VerticalTabs::preRenderVerticalTabs(). Skipping this would force us to
      // move all child groups to this array, making it an un-nestable.
      $element['group']['#groups'][$type] = [0 => []];
      $element['group']['#groups'][$type]['#group_exists'] = TRUE;
    }
    else {
      $element = HorizontalTabs::processHorizontalTabs($element, $form_state, FALSE);
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return array(
      'type' => 'horizontal',
    ) + parent::defaultSettings();
  }


  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $elements['type'] = array(
      '#type' => 'select',
      '#options' => $this->getTabTypes(),
      '#title' => t('Type'),
      '#default_value' => $this->getSetting('type'),
      '#required' => TRUE,
    );

    return $elements;
  }

  protected function getTabTypes() {
    return [
      'horizontal' => t('Horizontal tabs'),
      'vertical' => t('Vertical tabs')
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = parent::settingsSummary();
    $types = $this->getTabTypes();
    $summary[] = t('Rendered as @mode', array('@mode' => $types[$this->settings['type']]));

    return $summary;
  }

}

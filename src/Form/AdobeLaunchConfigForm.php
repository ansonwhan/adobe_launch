<?php

namespace Drupal\adobe_launch\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Path\PathValidatorInterface;

/**
 * Adobe Launch Snippet Manager Module Config form.
 */
class AdobeLaunchConfigForm extends ConfigFormBase {

  /**
   * The path validator.
   *
   * @var \Drupal\Core\Path\PathValidatorInterface
   */
  protected $pathValidator;

  /**
   * The ConfigFactory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs an AdobeLaunchConfigForm object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The factory for configuration objects.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   The path validator.
   */
  public function __construct(ConfigFactoryInterface $config_factory, PathValidatorInterface $path_validator) {
    parent::__construct($config_factory);
    $this->pathValidator = $path_validator;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('path.validator')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'adobe_launch.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'adobe_launch_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->getEditable('adobe_launch.settings');

    // Adobe Launch Settings.
    $form['settings']['adobe-launch-service'] = [
      '#type' => 'details',
      '#title' => $this->t('Adobe Launch Service settings'),
      '#description' => $this->t("
      <ol>
      <li>Define the Dev & Prod adobe launch snippet paths in this module's config.</li>
      <li>Then save the changes and clear caches.</li>
      </ol>"),
      '#open' => TRUE,
    ];

    $form['settings']['adobe-launch-service']['adobe-launch-enable'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Enable Adobe Launch'),
      '#description'   => $this->t('Will enable the Adobe Launch Tag Management script insertion on the site based on the Path rules defined below and if the environment url is defined.'),
      '#default_value' => $config->get('adobe-launch-enable'),
    ];

    $form['settings']['adobe-launch-service']['init-js-array'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Include javascript array object initializer'),
      '#description'   => $this->t('<p>If enabled, loads vanilla javascript array object initializer script included with this module and displayed below:</p>
      <pre>
      window.digitalData = { events: [] };
      window.DTM_DATA = window.DTM_DATA || [];
      </pre>'),
      '#default_value' => $config->get('init-js-array'),
    ];

    $form['settings']['adobe-launch-service']['target-adobe-launch-environment'] = [
      '#type' => 'select',
      '#title' => $this->t('Target Adobe Launch Environment'),
      '#default_value' => $config->get('target-adobe-launch-environment') ? $config->get('target-adobe-launch-environment') : 'staging',
      '#description' => $this->t('The selected value here will determine which Adobe Launch script to inject into the <head> section of the html.'),
      '#options' => [
        'staging' => 'staging/dev/testing',
        'prod' => 'production',
      ],
    ];
    $form['settings']['adobe-launch-service']['adobe-launch-async'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Load asynchronously?'),
      '#default_value' => $config->get('adobe-launch-async'),
      '#description' => $this->t('Check to load Adobe Launch script asynchronously (recommend: checked)'),
    ];
    $form['settings']['adobe-launch-service']['adobe-launch-prod-url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Adobe Launch "Production Url"'),
      '#default_value' => $config->get('adobe-launch-prod-url'),
      '#description' => $this->t('This should be a protocol agnostic url to the production version of the Adobe Launch js library, such as //assets.adobedtm.com/launch-randomIdentifierString1.min.js
      '),
    ];
    $form['settings']['adobe-launch-service']['adobe-launch-staging-url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Adobe Launch "Staging Url"'),
      '#default_value' => $config->get('adobe-launch-staging-url'),
      '#description' => $this->t('This should be a protocol agnostic url to the staging version of the Adobe Launch js library, such as //assets.adobedtm.com/launch-randomIdentifierString2-staging.min.js'),
    ];
    $form['settings']['adobe-launch-service']['adobe-launch-registrant'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Registrant's Email"),
      '#default_value' => $config->get('adobe-launch-registrant'),
      '#description' => $this->t('The email address under which this Adobe Launch subscription is registered'),
    ];

    // Paths section.
    $form['path-context'] = [
      '#type'  => 'details',
      '#title' => $this->t('Paths'),
      '#group' => 'settings',
      '#open' => TRUE,
    ];

    $default_admin_paths = ['/admin', '/admin/*', '/node/*/edit'];

    $form['path-context']['paths'] = [
      '#type'          => 'textarea',
      '#title'         => $this->t('Listed paths for exclusion or inclusion'),
      '#description'   => $this->t("Enter one path per line. The '*' character is a wildcard. An example path is %node-edit-wildcard for every node edit page, %admin-wildcard targets administration pages.", [
        '%node-edit-wildcard' => '/node/*/edit',
        '%admin-wildcard'     => '/admin/*',
      ]),
      '#default_value' => $config->get('paths') !== NULL ? $config->get('paths') : implode("\n", $default_admin_paths),
      '#rows'          => 10,
    ];

    $form['path-context']['paths_negate'] = [
      '#type'          => 'radios',
      '#title_display' => 'invisible',
      '#options'       => [
        1 => $this->t('Exclude on these paths'),
        0 => $this->t('Include on these paths'),
      ],
      '#default_value' => $config->get('paths_negate') !== NULL ? $config->get('paths_negate') : 1,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $url_field_ids = [
      'adobe-launch-prod-url',
      'adobe-launch-staging-url',
    ];

    foreach ($url_field_ids as $url_field_to_validate) {
      if (!$form_state->isValueEmpty($url_field_to_validate)) {
        $url = $this->pathValidator->getUrlIfValidWithoutAccessCheck("https:" . $form_state->getValue($url_field_to_validate));

        if (!$url || !$url->isExternal()) {
          $form_state->setErrorByName($url_field_to_validate, $this->t("The URL '%path' is invalid.", ['%path' => "https:" . $form_state->getValue($url_field_to_validate)]));
        }
        elseif (rtrim($form_state->getValue($url_field_to_validate), '/') !== $form_state->getValue($url_field_to_validate)) {
          $form_state->setValueForElement($form[$url_field_to_validate], rtrim($form_state->getValue($url_field_to_validate), '/'));
        }

      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $config = $this->configFactory->getEditable('adobe_launch.settings');

    $config
      ->set('adobe-launch-enable', $form_state->getValue('adobe-launch-enable'))
      ->set('target-adobe-launch-environment', $form_state->getValue('target-adobe-launch-environment'))
      ->set('adobe-launch-async', $form_state->getValue('adobe-launch-async'))
      ->set('init-js-array', $form_state->getValue('init-js-array'))
      ->set('adobe-launch-prod-url', $form_state->getValue('adobe-launch-prod-url'))
      ->set('adobe-launch-staging-url', $form_state->getValue('adobe-launch-staging-url'))
      ->set('adobe-launch-staging-url', $form_state->getValue('adobe-launch-staging-url'))
      ->set('adobe-launch-registrant', $form_state->getValue('adobe-launch-registrant'))
      ->set('paths', $form_state->getValue('paths'))
      ->set('paths_negate', $form_state->getValue('paths_negate'))
      ->save();
  }

}

/**
 * @file
 * Condition UI behaviors.
 */

(($, window, Drupal) => {
  /**
   * Provides the summary information for the condition vertical tabs.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches the behavior for the condition summaries.
   */
  Drupal.behaviors.conditionSummary = {
    attach: (context) => {
      const $context = $(context);
      $context
        .find('.vertical-tabs__item, .vertical-tabs__pane')
        .drupalSetSummary((summaryContext) => {
          if ($(summaryContext).find('input.enable:checked').length) {
            return Drupal.t('Restricted');
          }
          return Drupal.t('Not restricted');
        });
    },
  };
})(jQuery, window, Drupal);

define(['jquery', 'core/ajax', 'core/notification', 'core/str', 'core/modal_factory'],
function ($, ajax, notification, str, ModalFactory) {

  return {
    init: (courseid, version) => {

      function confirmAction(titleKey, bodyKey, onConfirm) {

        str.get_strings([
          { key: titleKey, component: 'block_mad2api' },
          { key: bodyKey, component: 'block_mad2api' },
          { key: 'confirm', component: 'block_mad2api' },
          { key: 'cancel', component: 'block_mad2api' }
        ]).done(function (strs) {

          ModalFactory.create({
            title: strs[0],
            body: strs[1],
            type: ModalFactory.types.SAVE_CANCEL
          }).then(modal => {

            // ✅ textos dos botões via strings
            modal.setButtonText('save', strs[2]);
            modal.setButtonText('cancel', strs[3]);

            // ✅ evento confiável
            modal.getRoot().on('click', '[data-action="save"]', function () {
              onConfirm();
              modal.hide();
            });

            modal.getRoot().on('click', '[data-action="cancel"]', function () {
              modal.hide();
            });

            modal.show();
          });

        }).fail(notification.exception);
      }

      // =========================
      // ENABLE
      // =========================
      $('#enable-settings').on('click', function(event) {
        event.preventDefault();

        confirmAction(
          'confirm_enable_title',
          'confirm_enable_body',
          enableCourse
        );
      });

      function enableCourse() {
        const request = {
          methodname: 'block_mad2api_enable_course',
          args: { courseid }
        };

        const promise = ajax.call([request])[0];

        promise.fail(notification.exception);

        promise.done(response => {
          const body = response[0];

          if (body.error) {
            str.get_strings([
              { key: 'error_modal_title', component: 'block_mad2api' },
              { key: 'error_modal_body', component: 'block_mad2api' },
              { key: 'error_alert_body', component: 'block_mad2api' }
            ]).done(function (strs) {
              if (version > 2016120500) {
                ModalFactory.create({
                  title: strs[0],
                  body: strs[1]
                }).then(modal => modal.show());
              } else {
                alert(strs[2]);
              }
            });
            return;
          }

          if (body.enabled) {
            $('#disable-settings').removeClass('disabled');
            $('#enable-settings').addClass('disabled');

            $('#access-dashboard').attr('href', body.url);
            $('#access-dashboard').removeClass('disabled');
            $('#lti-lanse').removeClass('disabled');
          }
        });
      }

      // =========================
      // DISABLE
      // =========================
      $('#disable-settings').on('click', function(event) {
        event.preventDefault();

        confirmAction(
          'confirm_disable_title',
          'confirm_disable_body',
          disableCourse
        );
      });

      function disableCourse() {
        const request = {
          methodname: 'block_mad2api_disable_course',
          args: { courseid }
        };

        const promise = ajax.call([request])[0];

        promise.fail(notification.exception);

        promise.done(response => {
          const body = response[0];

          if (body.disabled) {
            $('#enable-settings').removeClass('disabled');
            $('#disable-settings').addClass('disabled');
            $('#access-dashboard').addClass('disabled');
            $('#lti-lanse').addClass('disabled');
          }
        });
      }
    }
  };
});

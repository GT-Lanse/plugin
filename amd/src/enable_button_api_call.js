define(['jquery', 'core/ajax', 'core/notification', 'core/str'],
function ($, ajax, notification, str) {

  return {
    init: (courseid, version) => {

      function confirmAction(titleKey, bodyKey, onConfirm) {
        str.get_strings([
          { key: titleKey, component: 'block_mad2api' },
          { key: bodyKey, component: 'block_mad2api' },
          { key: 'confirm', component: 'block_mad2api' },
          { key: 'cancel', component: 'block_mad2api' }
        ]).done(function (strs) {
          // core/notification.saveCancel works across Moodle 4.x and 5.x
          // (core/modal_factory was deprecated in 4.3 and removed in 5.2).
          notification.saveCancel(
            strs[0],
            strs[1],
            strs[2],
            () => onConfirm(),
            () => {}
          ).then(modal => {
            if (modal && typeof modal.setButtonText === 'function') {
              modal.setButtonText('cancel', strs[3]);
            }
            return modal;
          }).catch(notification.exception);
        }).fail(notification.exception);
      }

      // core/notification.exception() titles its modal with the exception's
      // errorcode (e.g. "nopermissionmonitoring"), so web service failures
      // get a translated title and the server's message as the body instead.
      function showRequestError(ex) {
        if (!ex || !ex.message) {
          notification.exception(ex);
          return;
        }

        str.get_string('request_error_title', 'block_mad2api')
          .then(title => notification.alert(title, ex.message))
          .catch(notification.exception);
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

        promise.fail(showRequestError);

        promise.done(response => {
          const body = response[0];

          if (body.error) {
            str.get_strings([
              { key: 'error_modal_title', component: 'block_mad2api' },
              { key: 'error_modal_body', component: 'block_mad2api' },
              { key: 'error_alert_body', component: 'block_mad2api' }
            ]).done(function (strs) {
              if (version > 2016120500) {
                notification.alert(strs[0], strs[1]).catch(notification.exception);
              } else {
                alert(strs[2]);
              }
            }).fail(notification.exception);
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

        promise.fail(showRequestError);

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

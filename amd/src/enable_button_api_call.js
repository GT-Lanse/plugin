define(['jquery', 'core/ajax', 'core/notification', 'core/str'],
  function($, ajax, notification, str) {
    return {
      init: (courseId, version) => {
        $('#enable-settings').click(event => {
          event.preventDefault();

          const args = {};
          args.courseId = courseId;

          const request = {
            methodname: 'block_mad2api_enable_course',
            args: args
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
              ]).done(function(strs) {
                if (version > 2016120500) {
                  require(['core/modal_factory'], (ModalFactory) => {
                    ModalFactory.create({
                      title: strs[0],
                      body: strs[1]
                    }).then(modal => { modal.show(); });
                  })
                } else {
                  alert(strs[2]);
                }
              });

              return;
            }

            if (body.enabled) {
              $('#disable-settings').removeClass('disabled');
              $('#enable-settings').addClass('disabled');

              $("#access-dashboard").attr("href", body.url);
              $('#access-dashboard').removeClass('disabled');
              $('#lti-lanse').removeClass('disabled');
            }
          });
        });

        $('#disable-settings').click(event => {
          event.preventDefault();

          const args = {};
          args.courseId = courseId;

          const request = {
            methodname: 'block_mad2api_disable_course',
            args: args
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
        });
      }
    };
  });

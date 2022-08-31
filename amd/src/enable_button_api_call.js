define(['jquery', 'core/ajax', 'core/notification', 'core/modal_factory', 'core/str'],
      function($, ajax, notification, ModalFactory, str) {
  return {
    init: courseId => {
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
              {key: 'error_modal_title', component: 'block_mad2api'},
              {key: 'error_modal_body', component: 'block_mad2api'}
            ]).done(function(strs) {
              ModalFactory.create({
                title: strs[0],
                body: strs[1]
              }).then(modal => { modal.show(); });
            });

            return;
          }

          if (body.enabled) {
            $('#disable-settings').removeClass('disabled');
            $('#enable-settings').addClass('disabled');

            $("#access-dashboard").attr("href", body.url);
            $('#access-dashboard').removeClass('disabled');
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
          }
        });
      });
    }
  };
});

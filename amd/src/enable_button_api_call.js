define(['jquery', 'core/ajax', 'core/notification'], function($, ajax, notification) {
  return {
    init: courseId => {
      $('#enable-settings').click((event) => {
        event.preventDefault();

        const args = {};
        args.courseId = courseId;

        const request = {
          methodname: 'block_mad2api_enable_course',
          args: args
        };
        const promise = ajax.call([request])[0];

        promise.fail(notification.exception);
        promise.done((response) => {
          const body = response[0];

          if (body.enabled) {
            $('#disable-settings').removeClass('disabled');
            $('#enable-settings').addClass('disabled');

            $("#access-dashboard").attr("href", body.url);
            $('#access-dashboard').removeClass('disabled');
          }
        });
      });

      $('#disable-settings').click((event) => {
        event.preventDefault();

        const args = {};
        args.courseId = courseId;

        const request = {
          methodname: 'block_mad2api_disable_course',
          args: args
        };
        const promise = ajax.call([request])[0];

        promise.fail(notification.exception);
        promise.done((response) => {
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

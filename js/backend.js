/*global $, dotclear */
'use strict';

Object.assign(dotclear.msg, dotclear.getData('activityReport'));

$(() => {
  $('#form-logs').on('submit', function () {
      return window.confirm(dotclear.msg.confirm_delete);
  });
});
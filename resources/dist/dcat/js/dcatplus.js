document.addEventListener('DOMContentLoaded', function() {
  var copy_class = document.getElementsByClassName('copy');
  if (copy_class) {
    var clipboard = new ClipboardJS('.copy', {
      text: function (trigger) {
        return trigger.getAttribute('copy-text');
      }
    });
    clipboard.on('success', function(e) {
      e.clearSelection();
      layer.msg('已复制');
    });
    clipboard.on('error', function(e) {
      e.clearSelection();
      layer.msg('复制内容失败');
    });
  }
});
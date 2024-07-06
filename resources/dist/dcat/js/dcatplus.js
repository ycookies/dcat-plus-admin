(function () {
  var clipboard = new ClipboardJS('.copy');
  clipboard.on('success', function(e) {
    e.clearSelection();
    layer.msg('已复制');
  });
  clipboard.on('error', function(e) {
    e.clearSelection();
    layer.msg('复制内容失败');
  });
})()
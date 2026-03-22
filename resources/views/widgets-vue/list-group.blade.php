
<div class="{{$id}}" id="{{$id}}" style="margin-bottom: 20px">
    <div class="box-loading sp sp-circle"></div>
    <ul class="list-group" v-if="load_status">
        <li v-for="(item, index) in items" class="list-group-item d-flex justify-content-between align-items-center">
           <a :href="item.link" target="_blank">@{{ item.title }}</a>
            <span class="badge badge-secondary badge-pill">@{{ item.datetime }}</span>
        </li>
    </ul>
</div>
<script>
    Dcat.ready(function () {
        new Vue({
            el: `#{{$id}}`,
            data: {
                load_status:false,
                items: []
            },
            created:function(){
                let that = this;
                $.ajax({
                    url: '{!! $ajax_url !!}',
                    type: '{{$ajax_method}}', // 设置请求方法为POST
                    headers:{!! $ajax_headers !!},
                    data:{!! $ajax_data !!},
                }).then(function(resp) {
                    console.log(resp);
                    if (resp.code == 200) {
                        that.items = resp.data;
                        that.load_status = true; // 设置load_status为true
                        $('#{{{$id}}}').find('.box-loading').hide();
                        console.log();
                    }
                });
            },
            mounted: function() {
                // 在Vue实例渲染完成后执行的操作

                //document.querySelector(`#${{{$id}}}`).querySelector('.box-loading').style.display = 'none'; // 隐藏box-loading
            }
        });
    });

</script>

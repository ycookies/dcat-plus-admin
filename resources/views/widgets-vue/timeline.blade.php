<style>
    .tpl-box {
        display: none;
    }

    .timeline-box .timeline {
    }

    .timeline-box .timeline > div > .timeline-item {
        box-shadow: 0 0 1px rgba(0, 0, 0, .125), 0 1px 3px rgba(0, 0, 0, .2);
    }

    .timeline-box ul {
        padding-left: 20px;
    }

    .timeline-box li {
        list-style-type: disc;
    }

    .timeline-box .time-label {
        margin-bottom: 15px;
        margin-right: 10px;
        position: relative;
    }
</style>

{{--<div class="ppp">
    <h3>这是alpine 示例</h3>
    <div x-data="{ posts: [{id:1,title:'1111',content:'2222',},{id:2,title:'3333',content:'4444'}] }" x-init="fetchPosts()">
        <template x-for="post in posts" :key="post.id">
            <div>
                <h2 x-text="post.title"></h2>
                <p x-text="post.content"></p>
            </div>
        </template>

        <script>
            function fetchPosts() {
                fetch('https://demo.saishiyun.net/api/dcatplus/packageUplogs')
                    .then(response => response.json())
                    .then(data => {
                        console.log('这是packageUplogs');
                        console.log(data);
                        console.log('这是一个说明');
                        posts = data.data;
                    });
            }
        </script>
    </div>
</div>--}}
<div class="timeline-box" id="{{$id}}">
    <div class="box-loading sp sp-circle"></div>
</div>

<div class="tpl-box" id="timeline-box-tpl">
    <div class="timeline">
        <div v-for="(item, index) in items">
            <div class="time-label">
                <span class="bg-green">2024-07-10</span>
            </div>

            <div>
                <i class="fa fa-bullseye bg-green"></i>
                <div class="timeline-item">
                    <span class="time"><i class="fas fa-clock"></i>09:12</span>
                    <h3 class="timeline-header">这是一个标题</h3>
                    <div class="timeline-body">
                        这是一个内容
                    </div>
                </div>
            </div>
        <div>
        <div>
            <i class="fa fa-clock-o"></i>
        </div>
    </div>
</div>


{{--<div class="timeline-box" id="#{{$id}}">
<div class="box-loading sp sp-circle"></div>
<div class="timeline">
<div v-if="load_status">
<div v-for="(item, index) in items">
<div v-if="item.time_label">
<div class="time-label">
    <span class="bg-green"> @{{  item.time_label }}</span>
</div>
</div>
<div>
<i class="fa fa-bullseye bg-green"></i>
<div class="timeline-item">
<span class="time"><i class="fas fa-clock"></i>@{{  item.time }}</span>
<h3 class="timeline-header">@{{  item.title }}</h3>
<div class="timeline-body">
    <div v-html="item.content"></div>
</div>
</div>
</div>
</div>
<div>
<i class="fa fa-clock-o"></i>
</div>
</div>
</div>
</div>--}}

<script>
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
                    //that.load_status = true; // 设置load_status为true
                }
            });
        },
        mounted: function() {
            // 在Vue实例渲染完成后执行的操作
            $('#{{$id}}').html($('#timeline-box-tpl').html());
            //document.querySelector(`#${{{$id}}}`).querySelector('.box-loading').style.display = 'none'; // 隐藏box-loading
        }
    });
</script>
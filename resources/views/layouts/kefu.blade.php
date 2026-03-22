<style>
    .web-icon {
        /*background: url(../../yasuotu/images/cexuanfu.png) no-repeat*/
        background: url({{asset('/vendor/dcat-admin/images/kefu_float.png')}}) no-repeat;
    }
    .serve-kefu {
        width: 70px;
        position: fixed;
        right: 0px;
        top: 265px;
        z-index: 1050;
        box-sizing: content-box;
    }

    .ml_50 {
        margin-left: 0px;
        padding: 0px;
    }

    .serve-kefu ul .web-icon:hover {
        cursor: pointer
    }

    .serve-kefu ul .web-icon {
        display: block;
        width: 68px;
        height: 78px;
        transition: all .3s;
        position: relative;
        border: 1px solid #f1f1f1;
    }

    .footer,.serve-kefu ul .kefu-back-icon,.link {
        display: none;
    }
    .serve-kefu .kefu-wx-icon {
        background-position: 0 0
    }

    .serve-kefu .kefu-qq-icon {
        background-position: 0 -78px
    }
    .serve-kefu .kefu-help-icon {
        background-position: 0 -156px
    }
    .serve-kefu .kefu-help-icon a{
        width: 100%;
        height: 100%;
        display: inline-block;
    }
    .serve-kefu .kefu-question-icon {
        background-position: 0 -236px;
    }
    .serve-kefu .kefu-question-icon a{
        width: 100%;
        height: 100%;
        display: inline-block;
    }
    .serve-kefu .kefu-wenjuan-icon{
        background-position: 0 -156px
    }
    .serve-kefu .kefu-messageBoard-icon{
        background-position: 0 -236px
    }
    .serve-kefu .kefu-back-icon {
        /*background-position: 0 -313px*/
        background-position: 0 -236px
    }


    .serve-kefu ul .kefu-wx-icon:hover {
        background-position: -68px 0
    }

    .serve-kefu ul .kefu-qq-icon:hover {
        background-position: -68px -78px;
    }
    .serve-kefu .kefu-help-icon:hover {
        background-position: -68px -156px;
    }
    .serve-kefu .kefu-question-icon:hover {
        background-position: -68px -236px;
    }
    .serve-kefu .kefu-wenjuan-icon:hover{
        background-position: -68px -156px
    }
    .serve-kefu .kefu-messageBoard-icon:hover{
        background-position: -68px -236px
    }
    .serve-kefu ul .kefu-back-icon:hover {
        /*background-position: -68px -313px;*/
        background-position: -68px -236px
    }

    .gzh_wrap {
        bottom: auto !important;
    }
    .kf_wrap{
        position: absolute;
        padding-right: 8px;
        left: 95px;
        border-radius: 5px;
        bottom: -237px;
    }


    .serve-kefu ul li:hover .kf_wrap {
        left: -200px !important
    }

    .kefu-qq{
        width: 192px;
        height: auto;
        top: 0;
        color: #333;
        font-size: 13px;
        background: #fff;
        box-shadow: 0 0 10px #daf1f8;
    }
    .kefu-qq>p{
        width: 100%;
        height: 45px;
        line-height: 45px;
        text-align: center;
        border-bottom: 1px solid #f1f1f1;
        color: #000;
        font-weight: bold;
        font-size: 14px;
    }
    .kefu-qq ul{
        padding: 10px;
    }
    .kefu-qq ul div{
        display: flex;
        position: relative;
        padding-left: 30px;
        margin-bottom: 10px;
        white-space: nowrap;
        font-size: 12px;
    }
    .kefu-qq ul div.kftitle{
        font-size: 14px;
        color: #000;
        font-weight: bold;
    }
    .kfBtn{
        display: block;
        width: 86px;
        height: 27px;
        text-align: center;
        line-height: 27px;
        /*background: #25aad6;*/
        background: #0084e9;
        color: #fff;
    }
    a.kfBtn:hover{
        color: #fff;
    }
    .kefu-wx {
        width: 192px;
        height: 232px;
        padding: 20px;
        top: 0;
        color: #333;
        text-align: center;
        font-size: 13px;
        background: #fff;
        box-shadow: 0 0 10px #daf1f8;
        box-sizing: border-box;
    }


    .kefu-wx img {
        padding: 5px;
        display: block;
        width: 148px;
        height: 148px;
        box-sizing: content-box;
    }

    .qkfIcon{
        position: absolute;
        left: 0;
        width: 23px;
        height: 18px;
        background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABcAAAASCAYAAACw50UTAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyFpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNS1jMDE0IDc5LjE1MTQ4MSwgMjAxMy8wMy8xMy0xMjowOToxNSAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIChXaW5kb3dzKSIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDozQTY2NjE3QTExQTcxMUVBOUZGNDhEMjQxNEFFOTc0MSIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDozQTY2NjE3QjExQTcxMUVBOUZGNDhEMjQxNEFFOTc0MSI+IDx4bXBNTTpEZXJpdmVkRnJvbSBzdFJlZjppbnN0YW5jZUlEPSJ4bXAuaWlkOjNBNjY2MTc4MTFBNzExRUE5RkY0OEQyNDE0QUU5NzQxIiBzdFJlZjpkb2N1bWVudElEPSJ4bXAuZGlkOjNBNjY2MTc5MTFBNzExRUE5RkY0OEQyNDE0QUU5NzQxIi8+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+cQXgYQAAAiFJREFUeNqs1FuITWEUwPE9Z8zloDlIuY1xnzKKJpNQLlHyQLnV1JSHyYMXeZiMxJMkD0oK5da454VEeVGmJA/KpYQo4zKakQhlXMft/9V/1057H+fBql/7nH32Wfvba61vl0XZUYEJWIwGDPJ8Dl/wDFfxGH1pCcoyEg9HKxpx0yRdJq1ELeZiAT7iFO6WknwktuMrtphwKkZgMPrxDk/w3kUsx0lcwq840YC/EhfQjm7sxCSs8oZ9qvC6ENdxDPexFZ9xJa0UoZYtOOLnGf6x3donowpLcQIbPTff62vTko/CATS50sNYFxWPcehAs9/3Ym1ckVziwkaPt7AGva6sWLzAGZ9iIk5jDqrj5KFRR7HP2uUxGjfwI/p3XMNry3jbsT2P+pB8l48fxu856vATjyxVISNp3l6EBfRgGH7jDZaEEoXk87y43x/LPYZxW2+j0iJMUpub61tirOPj9FxidIa4kl4bMhl3sBBDU5LX4zs++YQfPD/eY2e5DQzjU4OXuOzE1NnQFZiCt64wlGkRVju2BTfRBW/SbL/akju0yU3wENOw2UkIW3+bN+9xxkPDz/lq2IN7OISDeOBwFI1QjrNurBBjrf9M+xKeZj92+PsyHLexRV9ccczGSpN12Y+B9maMo9fhdGzAbl8JJSWPXEmDpahy9LrdQK+wCbPc3Z2lvHKzotIbtLoj8/Yh7Myn0X+IGifqomNXnXXhHwEGACTAdN7hjwpdAAAAAElFTkSuQmCC) no-repeat;
        -webkit-background-size: 100% 100%;
        background-size: 100% 100%;
    }
    .wxkfIcon{
        position: absolute;
        left: 0;
        width: 22px;
        height: 18px;
        background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABYAAAASCAYAAABfJS4tAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyFpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNS1jMDE0IDc5LjE1MTQ4MSwgMjAxMy8wMy8xMy0xMjowOToxNSAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIChXaW5kb3dzKSIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDoyMTkxQUVBMzIxM0UxMUVBOERDRkU2NzJGOThBQzQ4RSIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDoyMTkxQUVBNDIxM0UxMUVBOERDRkU2NzJGOThBQzQ4RSI+IDx4bXBNTTpEZXJpdmVkRnJvbSBzdFJlZjppbnN0YW5jZUlEPSJ4bXAuaWlkOjIxOTFBRUExMjEzRTExRUE4RENGRTY3MkY5OEFDNDhFIiBzdFJlZjpkb2N1bWVudElEPSJ4bXAuZGlkOjIxOTFBRUEyMjEzRTExRUE4RENGRTY3MkY5OEFDNDhFIi8+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+E8RIGAAAAgZJREFUeNqU1E9IVFEUx/H3aphwAqEmpVS0gkLahAtBqE1C4h+yhUiLiDKJkIgxXSgujCAKctUmSFQMI6JECRcatYmynS5EAxW0SKSyWvTH/on2PfATLg/HeXPgwwxv3px33j3nXj8ej3tO+DiJWuzDNqzhN2bwCCNeiPCdxEW4rSQDeImPelgOSlGtB13GVJjEJejHDdxJUUwjmnEKr5PdtDUWi+3S69kf+pCnpVjAsu7LwGlV+wTvcRcP8SNZ4la9/nVdu4qbmMW4rh3HYxTgAd5gP44kW3NLfIvPa6rCU8JJDOGnrn3ANLrxDYeRjQuYUGHfg2s8ocZ8DtHss7iiJVnQg3OxA2Noxzu7MaKbYikSRtVcW4o2PMWK8/seJPAKdXhuS1Glm8Y3SWyNOoCjWl9fBa2HNfmZqu3F4BbNbssmVR9CjViCi3irJbCwwhY1sjb/96z5VvEcXw6iQaP0N5C43prszLdV+kmv/Q+ratwLjZ41OeHuvE7s1u5ywybGZv2SFy62266NOBd6NMPnnA0yj2IvvbAGR20p1i8ktBG+4D7+6A1sPSs0CUshElujx9yKh9GlNQrGL51sx1Ikt1EsxHm34nlVu1GMIh8d2mVz+vTU2ExNRxPK7VT0A+dxqqjUkZmlybCp2KkNFNGMnwmex+nEXonqHPmq068MJ2ze/wswADsVf8aaUNVHAAAAAElFTkSuQmCC) no-repeat;
        -webkit-background-size: 100% 100%;
        background-size: 100% 100%;
    }
    .phoneIcon{
        position: absolute;
        left: 0;
        width: 15px;
        height: 18px;
        background: url(data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAA8AAAASCAYAAACEnoQPAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyFpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNS1jMDE0IDc5LjE1MTQ4MSwgMjAxMy8wMy8xMy0xMjowOToxNSAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIChXaW5kb3dzKSIgeG1wTU06SW5zdGFuY2VJRD0ieG1wLmlpZDozMTk1OThGRjExQTcxMUVBQTUxOEY1RjMyNzZDNTVFQyIgeG1wTU06RG9jdW1lbnRJRD0ieG1wLmRpZDozMTk1OTkwMDExQTcxMUVBQTUxOEY1RjMyNzZDNTVFQyI+IDx4bXBNTTpEZXJpdmVkRnJvbSBzdFJlZjppbnN0YW5jZUlEPSJ4bXAuaWlkOjMxOTU5OEZEMTFBNzExRUFBNTE4RjVGMzI3NkM1NUVDIiBzdFJlZjpkb2N1bWVudElEPSJ4bXAuZGlkOjMxOTU5OEZFMTFBNzExRUFBNTE4RjVGMzI3NkM1NUVDIi8+IDwvcmRmOkRlc2NyaXB0aW9uPiA8L3JkZjpSREY+IDwveDp4bXBtZXRhPiA8P3hwYWNrZXQgZW5kPSJyIj8+IiwQegAAAbBJREFUeNqE1MtLVGEYx/G5qCNeRgRv4zJMJJHAjSTiBUEREYNEpJ1ohCJOK0FqowTeFiLURhH8A4IQF6KCl40QrdIBlUIJAkErAlFKHcfvE7+Bw2F0Xvjwzpw5z3nf93meM95gMOhhPMAMypGCSbzzJBk+5GMNh2jDS4yhJlmwPxAIjDP/QS9O8Q25aMIHx71PMI19nMRXrsV710M/6ghpjmu7iGAVT+PB6fjhCv6FbGQ5roWxh+dYQKkF38DrCi7DFc71PYTvSmqh5hELvkCBI7AHs/JP116gU6u/wRIqLPgIlbrJdjCEUUw5HjiBxyhWKf3GgpfRqpti+KydxBzBtoNhfMGi8pHhpUmsLJ/QrF3YebdVhcgdJX6IKqvzXz480tZX8FMrv9K5K9CAS/xWsM07XrVnSKtZY3zVuTaQijyV0lp4C/04i9fZxrHSP6dkXKPDno4B1KNRnbeJnP/Z1crxYT1+oIC7xrpq3u1z/dCFOry9Jzis5AZ9CdqyBc8wj8wEwSWao74EP1pyqhVoJRxUsvL0yloFXlvrus/sHu3oQ5GaJqo/CXsxPLcCDABI8lcpqOpsoQAAAABJRU5ErkJggg==) no-repeat;
        -webkit-background-size: 100% 100%;
        background-size: 100% 100%;
    }
</style>
{{--https://www.yasuotu.com/--}}
<div class="serve-kefu">
    <div class="newYearvip_wrap">

    </div>
    <ul class="ml_50">
        <li class="web-icon kefu-wx-icon">
            <div class="kf_wrap gzh_wrap">
                <div class="kefu-wx wxkf_ewm">
                    <img src="/vendor/dcat-admin/images/wxgzh_qrcode.jpg" alt="">
                    <p>扫描二维码关注</p>
                    <p>了解更多极速开发</p>
                </div>
            </div>
        </li>
        <li class="web-icon kefu-qq-icon">
            <div class="kf_wrap">
                <div class="kefu-qq">
                    <p>咨询客服</p>
                    <ul>
                        <div class="kftitle"><i class="qkfIcon"></i>
                            <p>QQ客服咨询</p>
                        </div>
                        <div>客服QQ: 3664839</div>
                        <div><a href="http://wpa.qq.com/msgrd?v=3&amp;uin=3664839&amp;site=qq&amp;menu=yes" target="_blank" class="kfBtn">联系客服</a></div>
                        <div class="kftitle"><i class="wxkfIcon"></i>
                            <p>微信客服咨询</p>
                        </div>
                        <div>
                            <img src="/vendor/dcat-admin/images/wx-qrcode.jpeg" alt="" style="width: 96px;height: 96px">
                        </div>
                        <div class="kftitle"><i class="phoneIcon"></i>
                            <p>联系电话</p>
                        </div>
                        <div>微信号: Q3664839</div>
                        <div>周一至周六08:30 - 17:00</div>
                    </ul>
                </div>
            </div>
        </li>
        <li class="web-icon kefu-help-icon">
            <a href="https://www.dcat-admin.com/books/dcatplus-admin/#/" target="_blank"></a>
        </li>
        <li class="web-icon kefu-question-icon">
            <a href="https://forum.saishiyun.net/d/55-dcat-admin-de-wen-juan-diao-cha-qi-dai-ni-de-can-ru" target="_blank"></a>
        </li>
        <li class="web-icon kefu-back-icon" onclick="$(&quot;html,body&quot;).animate({&quot;scrollTop&quot;:0});" style="display: none;"> </li>
    </ul>
</div>
@if(!empty($upimgdemo))
    <div class="upload-img-demo">
        <div class="form-upload-img-demo">
            <div class="el-image" >
              <img src="{{$upimgdemo}}" data-action='preview-img'  class="preview-img spotlight el-image__inner el-image__preview">
            </div>
            <div class="form-upload-img-demo-wrap" > 示例 </div>
        </div>
    </div>
    <div style="clear: both;"></div>
@endif
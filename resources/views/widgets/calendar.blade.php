<div class="calendar-box">
    <div id="{{$calendar_id}}"></div>

    <div id="external-events">
    </div>
    <div id="drop-remove">

    </div>

    <!-- 模态框 -->
    <div class="modal fade" id="{{$calendar_id}}-Modal" tabindex="-1" data-backdrop="static" role="dialog" aria-labelledby="eventModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content ">
                <div class="modal-header">
                    <h5 class="modal-title">事件详情</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">知道了</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>

    /* initialize the external events
     -----------------------------------------------------------------*/
    function ini_events(ele) {
        ele.each(function () {

            // create an Event Object (https://fullcalendar.io/docs/event-object)
            // it doesn't need to have a start or end
            var eventObject = {
                title: $.trim($(this).text()) // use the element's text as the event title
            }

            // store the Event Object in the DOM element so we can get to it later
            $(this).data('eventObject', eventObject)

            // make the event draggable using jQuery UI
            $(this).draggable({
                zIndex: 1070,
                revert: true, // will cause the event to go back to its
                revertDuration: 0  //  original position after the drag
            })

        })
    }

    ini_events($('#external-events div.external-event'))

    /* initialize the calendar
 -----------------------------------------------------------------*/
    //Date for the calendar events (dummy data)
    var date = new Date()
    var d = date.getDate(),
        m = date.getMonth(),
        y = date.getFullYear()

    var Calendar = FullCalendar.Calendar;
    var Draggable = FullCalendar.Draggable;

    var containerEl = document.getElementById('external-events');
    var checkbox = document.getElementById('drop-remove');
    var calendarEl = document.getElementById('{{$calendar_id}}');

    // initialize the external events
    // ----------------------------------------------------------------
    new Draggable(containerEl, {
        itemSelector: '.external-event',
        eventData: function (eventEl) {
            return {
                title: eventEl.innerText,
                backgroundColor: window.getComputedStyle(eventEl, null).getPropertyValue('background-color'),
                borderColor: window.getComputedStyle(eventEl, null).getPropertyValue('background-color'),
                textColor: window.getComputedStyle(eventEl, null).getPropertyValue('color'),
            };
        }
    });

    var calendar = new Calendar(calendarEl, {
        initialView: '{{$initialView}}',
        selectable: true,
        locale: '{{$locale}}',
        timeZone: '{{$timeZone}}',
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: '{{$header_toolbar_right_btn}}'
        },
        buttonText: { // 设置按钮文本为中文
            today: '今天',
            month: '月',
            week: '周',
            day: '日',
            list: '日程',
        },
        views: {
            list: {
                buttonText: '日程',
            }
        },
        themeSystem: 'bootstrap',
        events: {!! $items !!},
        eventClick: function(info) {
            // 检查事件是否允许弹出模态框
            if (info.event.extendedProps.showModal) {
                // 获取事件的标题和描述
                var eventTitle = info.event.title;
                var eventDescription = info.event.extendedProps.description;

                // 设置模态框的内容
                $('#{{$calendar_id}}-Modal').find('.modal-title').text(eventTitle);
                $('#{{$calendar_id}}-Modal').find('.modal-body').text(eventDescription);
                // 显示模态框
                $('#{{$calendar_id}}-Modal').modal('show');
            }

        },
        editable: true,
        droppable: false, // this allows things to be dropped onto the calendar !!!
        drop: function (info) {
            // is the "remove after drop" checkbox checked?
            if (checkbox.checked) {
                // if so, remove the element from the "Draggable Events" list
                info.draggedEl.parentNode.removeChild(info.draggedEl);
            }
        }
    });

    calendar.render();
    // $('#calendar').fullCalendar()

    /* ADDING EVENTS */
    var currColor = '#3c8dbc' //Red by default
    // Color chooser button
    $('#color-chooser > li > a').click(function (e) {
        e.preventDefault()
        // Save color
        currColor = $(this).css('color')
        // Add color effect to button
        $('#add-new-event').css({
            'background-color': currColor,
            'border-color': currColor
        })
    })
    $('#add-new-event').click(function (e) {
        e.preventDefault()
        // Get value and make sure it is not null
        var val = $('#new-event').val()
        if (val.length == 0) {
            return
        }

        // Create events
        var event = $('<div />')
        event.css({
            'background-color': currColor,
            'border-color': currColor,
            'color': '#fff'
        }).addClass('external-event')
        event.text(val)
        $('#external-events').prepend(event)

        // Add draggable funtionality
        ini_events(event)

        // Remove event from text input
        $('#new-event').val('')
    })
</script>
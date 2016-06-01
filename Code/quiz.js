$(document).ready(function () {

    //sort created questions
    $('.drop_area').sortable({
        placeholder: "ui-state-highlight",
        helper: 'clone',
        axis: 'y',
        cursor: 'crosshair',
        update: function (event, ui) {
            var question_order = $(this).sortable('serialize');
            $.ajax({
                type: "POST",
                url: _root + '/academy/quiz/order',
                data: question_order + "&id=" + $(this).attr('quiz_id'),
                id: $(this).attr('quiz_id'),
            });
        }
    });

    if ($('textarea#question').length > 0) {
        var ckQuestion = CKEDITOR.inline('question', {
            uiColor: '#829997',
            enterMode : CKEDITOR.ENTER_BR
        });
    }

    var editor = [];
    if ($('textarea.answers').length !== 0) {

        CKEDITOR.disableAutoInline = true;

        $('textarea.answers').each(function () {
            var key = $(this).closest('[id^="item-"]').attr('id');
            editor[key] = CKEDITOR.inline($(this).attr('id'), {
                uiColor: '#829997',
                enterMode : CKEDITOR.ENTER_BR
            });
        });
    }

    var ckeditor_quiz = [];
    if ($('textarea.quiz-info').length !== 0) {
        CKEDITOR.disableAutoInline = true;
        $('textarea.quiz-info').each(function () {
            ckeditor_quiz[$(this).attr('id')] = CKEDITOR.replace($(this).attr('id'), {
                uiColor: '#829997',
                height: 120,
                enterMode : CKEDITOR.ENTER_BR

            });
        });
    }
  
    if (($('textarea.comment').length !== 0) && (parseInt($('.quiz-name').attr('status')) == 2)) {
        var ckeditor_comment = [];
        CKEDITOR.disableAutoInline = true;
        $('textarea.comment').each(function () {

            ckeditor_comment[$($(this)).attr('id')] = CKEDITOR.replace($(this).attr('id'), {
                uiColor: '#829997',
                height: 120,
                enterMode : CKEDITOR.ENTER_BR,
                on :
                    {
                    instanceReady : function ( evt )
                        {
                            // Hide the editor top bar.
                           $("div#cke_"+evt.editor.name).hide();
                        }
                    }
            });

        });
    }

    //remove buttons and comment options when quiz is evaluated
    //view only
    var status = $('.quiz-name').attr('status');
    status = parseInt(status);
        if (status == 3) {
            $('input').attr("disabled", true);
            $("button").prop("disabled", true);
            $("button").remove();
            $(".comment-view").prop("disabled", true);
            $('a.comment-view').remove();
            $('a.comment-hide').remove();   
        }

     $(document).on('click', '.comment-view', function (e) {
        e.preventDefault();
        var id = $(this).attr('data-id');
        $("div#cke_"+id).show();
        $("#"+id+".grade").hide();
    });

    $(document).on('click', '.comment-hide', function (e) {
        e.preventDefault();

        var id = $(this).attr('data-id');
        var get_text = ckeditor_comment[id].getData();
        if (get_text !== "") {
            $("div#cke_"+id).hide();
            get_text = ckeditor_comment[id].getData();
            $("#"+id+".grade").html(get_text);
            $("#"+id+".grade").show();
        }else{
            $("div#cke_"+id).hide();
            $("#"+id+".grade").html(t('NO_COMMENT_IS_LEFT', 0, false));
            $("#"+id+".grade").show();
        }
    });

    $(document).on('click', '.grade_quiz', function (e) {
    $(this).prop('disabled',true);
    var empty = 0;
    var error = 0;
        var comments = answerValuesEdit(ckeditor_comment);
        var map = {};
            $("input.grade-answers").each(function() {
                map[$(this).attr("answer-id")] = $(this).val();
            });
    
        $('input.grade-answers').each(function() {
          if ($(this).val() === '') {
            empty = 1;
          }
        });

        $('input.grade-answers').each(function() {
          if (parseInt($(this).val()) > parseInt($(this).attr('max-points'))) {
            error = 1;
          }
        });

    if (error != 1) {
        if (empty != 1) {
            $.ajax({
                type: "POST",
                url: _root + '/academy/quiz/final-assessment',
                data: {
                    try_id: $(this).attr('try_id'),
                    quiz_id: $('div.quiz-name').attr('quiz_id'),
                    comments: comments,
                    points: map,
                }
            }).then(function()  {
								location.reload();
								$('.grade_quiz').remove();
						}); 
								
        }else{
            alert("Plase set grade to all open qestions");
            $(this).prop('disabled',false);
        }
    }else{
        alert("Points for open answer are greater than max points for the question");
        $(this).prop('disabled',false);
    }
    //location.reload();	
    });

    $(document).on('click','button#hide-graded-questions', function(){
    if ($('div.hide-close-questions').is(":visible")) {
        $('div.hide-close-questions').hide();
        $('article.question-name[data-type="multichoice"]').hide();
        $('article.question-name[data-type="open-choices"]').hide();
        var text = $(this).text();
        $(this).text($('p#change_text').text());
        $('p#change_text').text(text);
    }else{
            $('div.hide-close-questions').show();
            $('article.question-name[data-type="multichoice"]').show();
            $('article.question-name[data-type="open-choices"]').show();
            var text1 = $(this).text();
            $(this).text($('p#change_text').text());
            $('p#change_text').text(text1);
        }
    });


    function checkCKEditorHideShow(selector, id, description_div, ckeditor_id) {
//@todo checks
        if (id != "tries" && id != "duration") {
            var get_text = ckeditor_quiz[ckeditor_id].getData();
            $(description_div).html(get_text);
        } else {
            var text = $(selector).val();
            if (text === "") {
                text = 0;
            }
            $(description_div).html(text);
        }
    }

    function CKEditorHideShow(selector, status) {
        var name = $(selector).attr('id');
        var id = name.split('-')[1];
        var ckeditor_div = "#" + id;
        var description_div = "#" + id + "-div";
        var ckeditor_id = "quiz-" + id;
        var input_id = "input#" + ckeditor_id;

        if (status) {
            $(ckeditor_div).show();
            checkCKEditorHideShow(input_id, id, description_div, ckeditor_id);
            $(description_div).hide();
        } else {
            if ($(ckeditor_div).is(":visible")) {
                $(ckeditor_div).hide();
                checkCKEditorHideShow(input_id, id, description_div, ckeditor_id);
                $(description_div).show();
            }
        }
    }

    $(document).on('click', '.edit', function (e) {
        e.preventDefault();
        CKEditorHideShow(this, true);
    });

    $(document).on('click', '.edit-hide', function (e) {
        e.preventDefault();
        CKEditorHideShow(this, false);
    });

    if ($('.cke_textarea_inline').length !== 0) {
        $(document).on('click', '.cke_textarea_inline ', function () {
            //editor.focus();
            $(this).on("focus");
            $('.input_fields_wrap').sortable("disable");
        });
    }

    //answer sort
    var answer_order = [];
    $('.input_fields_wrap').sortable({
        placeholder: "ui-state-highlight",
        axis: 'y',
        cursor: 'crosshair',
        stop: function (event, ui) {
            answer_order = $(this).sortable('toArray');

        }

    });

    function answerValuesEdit(ckeditor) {
        var answersObj = {};

        var counter = 0;
        for (var i in ckeditor) {

            if (!answersObj[counter]) {
                answersObj[counter] = {};
            }
            answersObj[counter].name = ckeditor[i].name;
            answersObj[counter].value = ckeditor[i].getData();
            counter++;
        }
        return answersObj;
    }

    function checkInstance(ckeditor) {
        for (var i in ckeditor) {
           if(ckeditor[i].checkDirty() == true){
                return true;
            }
        }
        return false;
    }
    

    $('.drag_question_type').css('cursor', 'pointer');
    $(document).on('click', '.drag_question_type', function () {
        var quiz_id = $('#question-types').attr('quiz_id');
        var type = $(this).attr('q-type');
        location.href = _root + '/quiz/new/' + quiz_id + '/' + type;
    });

    //back button
    $(document).on('click', '#back_button', function () {
        var quiz_id = $(this).attr('data-quiz-id');
        location.href = _root + '/quiz/view/' + quiz_id;
    });

    $(document).on('click', '#back_button_quiz', function () {
        location.href = _root + '/quiz';
    });

    $(document).on('click', 'a.delete-quiz', function (e) {

        e.preventDefault();

        if (ConfirmAlert(t('ARE_YOU_SHURE?', 0, false))) {

            var id = $(this).data('id');

            $.ajax({
                type: "POST",
                url: _root + '/academy/quiz/delete-quiz',
                dataType: 'JSON',
                data: {
                    id: id
                },
            }).done(function (resp) {
                quizesCrud.update();
            });
        }
    });


    function ConfirmAlert(alert_string) {
        var r = confirm(alert_string);
        if (r === true) {
            return true;
        } else {
            return false;
        }
    }

    //delete answer
    $(document).on('click', '.delete-answer', function () {

        var answer_id = $(this).data('id');
        var type = $('textarea[class="answers"]').attr('data-open');
        var parentLi = $(this).parent().parent().parent('li');

        if (ConfirmAlert(t('ARE_YOU_SHURE?', 0, false))) {

            delete editor['item-' + answer_id];
            removeFromArray(answer_order, 'item-' + answer_id);

            $(this).parent().parent().parent().hide('slow').remove();
            $.ajax({
                type: "POST",
                url: _root + '/academy/quiz/delete-answer',
                data: {
                    id: answer_id,
                    type: type,
                },
            }).done(function () {
                parentLi.remove();
                relocatePoints(this);
            });
        }
    });


    //edit button is clicked
    $('.drop_area .add_q').on('click', 'a.btn-default-edit', function (e) {
        e.preventDefault();
        var question_id = $(this).attr('data-q_id');
        location.href = _root + '/quiz/edit/' + question_id;
    });

    var total_points = $('#total-quiz-points').text();
    total_points = total_points.split(':');
    var max_points = parseInt(total_points[1]);

    $('div#max').text(max_points);


    function editQuizDetails(quiz_id) {
        var info = answerValuesEdit(ckeditor_quiz);
        var type = $('#quiz_type').serializeArray();

        if (info[0]['value'] !== "") {
            $.ajax({
                type: "POST",
                url: _root + '/academy/quiz/edit-quiz-details',
                dataType: 'JSON',
                data: {
                    id: quiz_id,
                    details: info,
                    type: type,
                },
            }).done(function () {
                alert("Successful update");
            });
        } else {
            alert(t('PLEASE_ENTER_QUIZ_NAME', 0, false));
        }
    }

    $(document).on('click', '.edit-quiz-info', function () {
        var quiz_id = $(this).attr('id');
        editQuizDetails(quiz_id);
    });

    $(document).on('click', '#deactivate-quiz', function () {
        var quiz_id = $(this).data('id');
        if ($('#active_state').attr('data-courses-exist') == 1) {
            alert("Courses have been added to the test and you can not deactivate it");
            return;
        }
        $.ajax({
            type: "POST",
            url: _root + '/academy/quiz/deactivate-quiz',
            dataType: 'JSON',
            data: {
                quiz_id: quiz_id,
            },
        }).done(function(){
            window.location.reload();
        });
    });

    $(document).on('change','#change_assessment', function(){
        if ($('#change_assessment').prop( "checked" )) {
            $('div#points_number').hide();
            $('div#points_percent').show();
        }else{
            $('div#points_number').show();
            $('div#points_percent').hide();
        }
    });

//remove buttons when quiz is active
var active = $('#active_state').attr('data-active');
    if (active == 1) {
        $('.drop_area').sortable('disable');
        $('div').prop("disabled", true);
        $('a').prop("disabled", true);
        $('a[data-target="#activate-quiz-modal"]').remove();
        $('input').prop("disabled", true);
        $('select').prop("disabled", true);
        $('.edit-quiz-info').remove();
    }

       

    //delete question
    $('.drop_area .add_q').on('click', '.btn-default-delete', function (e) {
        e.preventDefault();
        var question_id = $(this).attr('data-q_id');
        var quiz_id = $(this).attr('data-quiz_id');
        if (ConfirmAlert(t('ARE_YOU_SHURE?', 0, false))) {
            $(this).parent('li').hide("slow").remove("slow");
            $.ajax({
                type: "POST",
                url: _root + '/academy/quiz/delete-question',
                data: {
                    id: question_id,
                    quiz: quiz_id,
                },
            }).done(function () {
                $('div[data-q_id='+ question_id +']').remove();
                 window.location.reload();
            });
        }
    });

    function checkForEmptyFields(obj, type) {
        if (type != 'open') {
            var empty = 0;
            $.each(obj, function (index, value) {
                if (obj[index].value === "") {
                    alert("Please fill all empty fields!!!");
                    empty = 1;
                    return false;
                }
            });
            if (empty) {
                return false;
            }
        }
        return true;
    }

    //submit form for new question
    $('#submitNewQuestion').on('click', function () {

        var quiz_id = $('#quiz_id').val();

        // var answers = $('.input_fields_wrap input[id="answers"]').serializeArray();
        var check = $('.input_fields_wrap input[id="answer-status"]').serializeArray();
        var type = $('#new_question textarea').attr('data-type');
        if (check === "" && type !== "open" && type !== "open-choices") {
            alert("Please select true answer");
            return false;
        }

        var answersObj = answerValues();

        var question_points = $('input[id="question-points"]').val();
        // var points =  $('.input_fields_wrap input[id="points"]').serializeArray();
        var question_name = ckQuestion.getData();

        var open_question_points = "";
        if (type == "open") {
            open_question_points = $('input[id="question-points"]').serializeArray();
        }

        if (question_name === "") {
            alert("Please fill all empty fields");
            return false;
        }

        if (checkForEmptyFields(answersObj, type)) {

            if (type != "open-choices" && type != "open") {
                $.ajax({
                    type: "POST",
                    url: _root + '/academy/quiz/create-question',
                    dataType: 'JSON',
                    data: {
                        id: quiz_id,
                        question: question_name,
                        answers: answersObj,
                        check: check,
                        number: x,
                        points: question_points,
                        open_points: open_question_points,
                        type: type,
                    },
                    }).done(function(){
                        location.href = _root + '/quiz/view/' + quiz_id;
                    });
            } else {

                if (type == "open") {
                    var points = $('input#question-points').val();
                } else {
                    var points = $('.portlet-header input#answer-points').serializeArray();
                }


                var question_sum_points = 0;
                $('.portlet-header input#answer-points').each(function (i, selected) {
                    var string_points = $(selected).val();
                    question_sum_points += parseInt(string_points);
                });

                $.ajax({
                    type: "POST",
                    url: _root + '/academy/quiz/create-question',
                    dataType: 'JSON',
                    data: {
                        id: quiz_id,
                        question: question_name,
                        answers: answersObj,
                        check: check,
                        number: x,
                        points: points,
                        sum_points: question_sum_points,
                        type: type,
                    },
                }).done(function(){
                    location.href = _root + '/quiz/view/' + quiz_id;
                });
            }
        }
    });

    $('#submitEditQuestion').on('click', function (e) {
				e.preventDefault();
        var quiz_id = $('#back_button').attr('data-quiz-id');
        var answersObj = answerValues();
        var answer_status = $('.input_fields_wrap input[id="answer-status"]').serializeArray();
        var question_id = $('#question').attr('question-id');
        var question_name = ckQuestion.getData();
        var questionType = $('#edit_question textarea').attr('data-type');

        if (question_name === "") {
            alert("Can't left empty values");
            return false;
        }

        var open_question_points = "";
        // if (questionType == "open") {
        open_question_points = $('input[id="question-points"]').val();
        //}

        if (questionType != 'open' && questionType != 'open-choices') {
            if (answer_status === '') {
                alert("Please check true answer");
                return false;
            }
        }

        if (checkForEmptyFields(answersObj, questionType)) {
            var answer_points = $('#question-points').val();

            if (questionType != 'open' && questionType != 'open-choices') {

                $.ajax({
                    type: "POST",
                    url: _root + '/academy/quiz/edit-question',
                    data: {
                        quiz_id: quiz_id,
                        id: question_id,
                        question: question_name,
                        answers: answersObj,
                        status: answer_status,
                        number: x,
                        points: answer_points,
                        open_points: open_question_points,
                        type: questionType,
                    },
                    success: function (resp) {

                    }
                }).done(function () {
                   location.href = _root + '/quiz/view/' + quiz_id;
                });
            } else {
                if (type == "open") {
                    var answer_points = $('input#question-points').val();
                } else {
                    var answer_points = $('.portlet-header input#answer-points').serializeArray();
                }

                var question_sum_points = 0;
                $('.portlet-header input#answer-points').each(function (i, selected) {
                    var string_points = $(selected).val();
                    question_sum_points += parseInt(string_points);
                });
                $.ajax({
                    type: "POST",
                    url: _root + '/academy/quiz/edit-question',
                    dataType: 'JSON',
                    data: {
                        quiz_id: quiz_id,
                        id: question_id,
                        question: question_name,
                        answers: answersObj,
                        status: answer_status,
                        number: x,
                        points: answer_points,
                        sum_points: question_sum_points,
                        open_points: open_question_points,
                        type: questionType,
                    },
                }).done(function () {
                   location.href = _root + '/quiz/view/' + quiz_id;
                });
            }
        }
    });

    function changeSort() {

        var _editor = {};
        if (answer_order.length === 0) {
            return;
        }

        for (var i in answer_order) {
            if (answer_order[i] === '')
                continue;
            _editor[answer_order[i]] = editor[answer_order[i]];
        }
        editor = _editor;
    }

    function answerValues() {
        var answersObj = {};
        changeSort();

        var counter = 0;
        for (var i in editor) {

            if (!answersObj[counter]) {
                answersObj[counter] = {};
            }

                answersObj[counter].name = editor[i].name;
                answersObj[counter].value = editor[i].getData();
                counter++;

        }
        return answersObj;
    }

    //change radio buttons without need of same name attr
    function radioChange() {
        $('input[type=radio][id=answer-status]').click(function () {
            $('input[type=radio]:checked').not(this).prop('checked', false);
        });
    }

    radioChange();

    //add new field for new question
    var max_fields = 20; //maximum input boxes allowed
    var wrapper = $(".input_fields_wrap"); //Fields wrapper
    var add_button = $(".add_field_button"); //Add button ID
    var type = $('textarea[id="question"]').attr('data-type');
    var attr = $('textarea[data-type = "open-choices"]').length;

    var x = 1; //initlal text box count
		var maxId = maxElement('input#answer-status','name') + 1;

    $(add_button).click(function (e) { //on add input button click
        e.preventDefault();
        if (x < max_fields) { //max input box allowed
            x++; //text box increment
						if (attr > 0) {
								maxId = maxElement('input#answer-points','number') + 1;
						}else{
								maxId = maxElement('input#answer-status','name') + 1;
						}

            var item = "item-" + maxId;
            var answer_name_field = "answer_" + maxId;
            var checkbox_type = "";
            if (type == "radio") {
                checkbox_type = "radio";
            } else if (type == "multichoice") {
                checkbox_type = "checkbox";
            }
            //check if the question is of type "open with choices"
            if (attr > 0) {
                $(wrapper).append('<div class="portlet-header push-double-bottom" id = ' + item + ' ><textarea contenteditable="true" class="answers" id="answer_' + maxId + '" type="text" name=' + answer_name_field + ' value="">\
								</textarea><br><strong>'+t('POINTS')+'</strong> :<input id="answer-points"name = ' + answer_name_field + ' number='+ maxId +' style = "display: inline-block; white-space: nowrap;"><span data-toggle="tooltip" title="' + t('DRAG_TO_REORDER') + '" class = "btn btn-default holder pull-right"><i class="icon-pointer" aria-hidden="true"></i>&nbsp;'+ t('REORDER') + '</span><hr><a href="#"><i class="icon-bin remove_field pull-right"></i></a></div></div>'); //add input box
                editor['item-' + maxId] = (CKEDITOR.inline(answer_name_field, {

                    uiColor: '#829997'
                }));

            } else {
                $(wrapper).append('<div class="portlet-header push-double-bottom" id = ' + item + ' ><textarea class="ckeditor answers" contenteditable="true" id="answer_' + maxId + '" type="text" name=' + answer_name_field + ' value=""></textarea><strong>'+t('POINTS')+' :<span id="answer-point" name=' + item + '>0</span></strong>\
								<br><label for="answer-status">' + t('ANSWER_STATUS') + '</label> <input id="answer-status" type= ' + checkbox_type + ' name = ' + maxId + ' style = "display: inline-block; white-space: nowrap;"><span data-toggle="tooltip" title="' + t('DRAG_TO_REORDER') + '" class = "btn btn-default holder pull-right"><i class="icon-pointer" aria-hidden="true"></i>&nbsp;'+ t('REORDER') + '</span><hr><a href="#"><i class="icon-bin remove_field pull-right"></i></a></div></div>'); //add input box
                //editor[$('.answers').length] =
                editor['item-' + maxId] = (CKEDITOR.inline(answer_name_field, {
                    uiColor: '#829997'
                }));
                if (answer_order.length !== 0) {
                    answer_order.push('item-' + maxId);
                }
            }
        }
        radioChange();
    });

    
    function getFieldName($elem) {
        var lastID;
        lastID = $elem.attr('name');
        return lastID;
    }

    function getFieldVal($elem) {

        var lastVAL;
        lastVAL = $elem.text();

        return lastVAL;

    }

//change points when change poins field and change true answer
    $(document).on('change', "input[type=radio]", function (e) {
        if ($('form#edit_question').length > 0) {
            var question_points = $('#question-points').val();
            var lastVAL = getFieldVal($(this).parent().find('#answer-point'));
            var lastNAME = $(this).attr('name');
            if ($(this).is(":checked")) {
                $(this).parent().find('#answer-point').text(question_points);
                $('#answer-point').not('div[name = ' + lastNAME + '][id=answer-point]').text(0);
            }
        }
    });


    $(document).on('change', "input#question-points", function (e) {
        var question_points = $(this).val();
        if($.isNumeric(question_points)){
             question_points = question_points / $('.input_fields_wrap #answer-status:checked').length;
        }else{
             question_points = 0;
        }
       

        $('input#answer-status:checked', '.input_fields_wrap').parent().find('#answer-point').text(question_points);
    });

    $(document).on('change', ".input_fields_wrap #answer-status", function (e) {
        relocatePoints(this);
    });

    function relocatePoints(selector) {
        var question_points = $('input#question-points').val();
        if($.isNumeric(question_points)){
            question_points = question_points / $('.input_fields_wrap #answer-status:checked').length;
        }else{
            question_points = 0;
        }

        $('input#answer-status:checked', '.input_fields_wrap').parent().find('#answer-point').text(question_points.toFixed(2));
        
        if (!$(selector).is(':checked')) {
            $(selector).parent().find('#answer-point').text(0);
        }
        $('.input_fields_wrap #answer-status').not($('.input_fields_wrap #answer-status:checked')).parent().find('#answer-point').text(0)
    }


    $(wrapper).on("click", ".remove_field", function (e) { //user click on remove text
        e.preventDefault();
        $(this).parent().parent().remove();
        var div_id = $(this).parent().parent().attr('id');
        var id = div_id.split('-');
        delete editor['item-' + id[1]];
        x--;
        relocatePoints(this);
    });

    function defaultValues(selector, defaultValue) {
        $(selector).focusin(function () {
            if (this.value == defaultValue) {
                this.value = '';
            }
        }).focusout(function () {
            if (this.value === '') {
                this.value = defaultValue;
            }
        });
    }

    defaultValues('#question', 'Default question value');
    defaultValues('#answers', 'Default answer value');


    $(document).on('click','#create-quiz',function(e){
        e.preventDefault();
        var data = $('#quiz_type').serializeArray();
 
                $.ajax({
                    type: "POST",
                    url: _root + '/academy/quiz/create-quiz',
                    dataType: 'JSON',
                    data: {
                        type: data,
                    },
                }).done(function (data) {
                    window.location =_root+ '/quiz/view/'+data[0];
                });
    
    });
    

    var $win = $(window), $doc = $(document), $root = $('html, body');
    // crud on quizes
    var quizesCrud = null;
    if ($('#quizeslist .item_template').length > 0) {
        quizesCrud = $('#quizeslist').crud({
            loadAddr: _root + '/academy/quiz/load',
            condition: true,
            order: {
                id: 0,
                value: 'desc',
            },
            /*filter: {
             id : 0,
             value : 'folder-receive-inbox',
             },*/

            manageCallbacks: {
                'update': function () {
                    setDisableStatus();
                }
            },
        });
    }


    // crud on quizes
    var quizesCrud1 = null;
    if ($('#quizeslist1').length > 0) {

        quizesCrud1 = $('#quizeslist1').crud({
            loadAddr: _root + '/academy/quiz/active-load',
            condition: true,
            manageCallbacks: {
                'update': function () {

                }
            },
        });
    }

    var quizesCrud2 = null;
    if ($('#quizeslist2').length > 0) {

        quizesCrud2 = $('#quizeslist2').crud({
            loadAddr: _root + '/academy/quiz/list-check-quizzes-load',
            condition: true,
            manageCallbacks: {
                'update': function () {

                }
            },
        });
    }

    var quizesCrud2 = null;
    if ($('#quizeslist3').length > 0) {

        quizesCrud2 = $('#quizeslist3').crud({
            loadAddr: _root + '/academy/quiz/student-quiz-view-load',
            condition: true,
            manageCallbacks: {
                'update': function () {

                }
            },
        });
    }

    $(document).on('mousemove', '.holder', function () {
        $('.input_fields_wrap').sortable("enable");
    });

    $(document).on('mousemove', '.cke_textarea_inline', function () {
        if ($('.ui-sortable-disabled')) {
            $('.input_fields_wrap').sortable("disable");
        }
    });


    $(document).on('click', '#copy-quiz', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        $.ajax({
            type: "POST",
            url: _root + '/academy/quiz/copy-quiz',
            dataType: 'JSON',
            data: {
                id: id
            },
        }).done(function (resp) {
            quizesCrud.update();
        });
    });


    // Share template prep
    $(document).on('click', 'a[data-target="#addCourse"]', function () {
        $('#addCourse').find('.modal-body').html('');

        $.ajax({
            type: "POST",
            url: _root + '/academy/quiz/add-course',
            data: {
                quiz_id: $(this).attr('data-id')
            },
            dataType: 'json'
        })
                .done(function (msg) {
                    if (msg.modal) {
                        $('#addCourse').modal('show');
                        $('#addCourse').find('.modal-body').html(msg.modal);
                    }
                });

        return false;
    });

    $(document).on('click', 'a[data-target="#activate-quiz-modal"]', function (e) {
        e.preventDefault();
        var quiz_id = $(this).data('quiz_id');
        if ($(this).attr('data-active') === 0) {
            if (!$( "a[data-quiz_id ="+quiz_id+"]" ).hasClass( 'change-status' )) {
                if (checkInstance(ckeditor_quiz)){
                    if (ConfirmAlert(t('DO_YOU_WANT_TO_SAVE_LAST_CHANGES?', 0, false))) {
                        editQuizDetails(quiz_id);
                    }
                }
            }
            $('#activate-quiz-modal').find('.modal-body').html('');
    
            $.ajax({
                type: "POST",
                url: _root + '/academy/quiz/active-settings',
                data: {
                    quiz_id: $(this).data('quiz_id')
                },
                dataType: 'json'
            }).done(function (msg) {
    
                if (msg.modal) {
                    $('#activate-quiz-modal').modal('show');
                    $('#activate-quiz-modal').find('.modal-body').html(msg.modal);
    
                }
            });
        }

    });


    $(document).on('click', '#activate-quiz-modal .btn-danger', function(){
        var quiz_id = $('.activate-quiz-container').attr('id');
        var check = 1;
     if ($('#min_percent').is(":visible")) {
                var min_type = "percent";
                var min_points = parseInt($('#min_percent').val());
            }else{
                var min_type = "number";
                var min_points = parseInt($('#min').val());
            }
    
            //var min_points = parseInt($('#min').val());
            if (!(min_points) && min_type == "number") {
                check = 0;
                alert("please select min points");
                return;
            }
            var total_points = $('.activate-quiz-container #points').text();
            var max_points = parseInt(total_points);
    
                if (!($('#min_percent').is(":visible"))) {
                    var min =  parseInt($('#min').val());
                    if (parseInt(total_points) < min) {
                        check = 0;
                        alert('MAX points are less than the MIN points. Please change them first!');
                    }
    
                var diff =  parseInt(total_points) - min;
                diff = diff/3;
                diff = (diff).toFixed(2);
                var for_4 = min + parseInt(diff);
                var for_5 = min + (diff*2);
    
                if (Math.floor(diff) <= 1) {
                    check = 0;
                    alert("Too small interval between grades");
                }
                }else{

                    if(parseInt($('#min_percent').val()) > 100){
                        check = 0;
                        alert('Percent must be less than 100');
                        return;
                    }

                    if(parseInt($('#min_percent').val()) === 0){
                        check = 0;
                        alert('Percent can not be 0');
                        return;
                    }   
                }
    
    
            if (!($.isNumeric(min_points) && min_points >= 0)) {
                check = 0;
                alert("illegal input");
            }
    
            if (check == 1) {
                $.ajax({
                        type: "POST",
                        url: _root + '/academy/quiz/activate-quiz',
                        dataType: 'JSON',
                        data: {
                            id: quiz_id,
                            active: 1,
                            min_points: min_points,
                            min_type: min_type,
                        },
                    }).done(function (resp) {
                        $('#activate-quiz-modal').modal('hide');
                        window.location.reload();
                    });
            }
    });

    $(document).on('keyup', '#add_course', function () {

        var search = $(this).val();
        //var quiz_type = $('#quiz_type_course').val();

        $('#selected_course').attr('value', '');
        $('#lectionResp').hide();
        $.ajax({
            type: "POST",
            url: _root + '/academy/quiz/course-search',
            data: {
                search: search
                        //quiz_type: quiz_type
            },
        }).done(function (resp) {
            $('#suggestionResp').show().html(resp);
        });
    });


    $(document).on('click', '.clickedCourse', function (e) {
        e.preventDefault();

        var course_id = $(this).data('id');

        $('#add_course').val($(this).text());
        $('#selected_course').val(course_id);
        $('#suggestionResp').hide();

        //if ($('#quiz_type_course').val() == 'mid-term') {

            $.ajax({
                type: "POST",
                url: _root + '/academy/quiz/course-lections',
                data: {
                    course_id: course_id,
                    type: $('#quiz_type_course').val()
                },
            }).done(function (resp) {
                $('#lectionResp').show().html(resp);
            });

        //}

    });

    $(document).on('click', 'a#copy-question', function (e) {
        e.preventDefault();
        var question_id = $(this).attr('data-q_id');
        var id = $(this).attr('data-quiz_id');
        $.ajax({
            type: "POST",
            url: _root + '/academy/quiz/copy-question',
            data_type: 'JSON',
            data: {
                id: id,
                question_id: question_id
            },
        }).done(function (resp) {
            alert("copied");
            window.location.reload();
        });

    });

    $(document).on('click', '#addCourse .btn', function () {
        //@todo validate data
        $(this).prop('disabled',true);
        var lesson_id = 0;
        var quiz_id = $('#quiz_id').val();
        var course_id = $('#selected_course').val();
        if ($('#selected_lesson').length > 0) {
            lesson_id = $('#selected_lesson').val();
        }

        if (!quiz_id || !course_id) {
            alert(t('PLEASE_CHOOSE_COURSE'));
            return;
        }

        var mandatory = $('#mandatory').is(':checked') ? 1 : 0;
        var disarrange_questions = $('#disarrange-questions').is(':checked') ? 1 : 0;
        var disarrange_answers = $('#disarrange-answers').is(':checked') ? 1 : 0;
        var tries = $('input[class="quiz-info"]').val();
        var duration = $('input#quiz-duration').val();

        var quiz_type_course = $('#quiz_type_course').val();

        if (quiz_type_course == 'mid-term') {
            if (!(lesson_id > 0)) {
                alert(t('PLEASE_SELECT_LESSON'));
                return;
            }
        }

        $.ajax({
            type: "POST",
            url: _root + '/academy/quiz/save-course',
            data_type: 'JSON',
            data: {
                quiz_id: quiz_id,
                course_id: course_id,
                lesson_id: lesson_id,
                mandatory: mandatory,
                tries: tries,
                duration: duration,
                disarrange_questions: disarrange_questions,
                disarrange_answers: disarrange_answers

            },
        }).done(function (resp) {
            $('#addCourse .btn').prop('disabled',false);
            quizesCrud.update();
            $('#addCourse').modal('hide');
        });
    });

    $(document).on('click', '.related_courses', function (e) {
        e.preventDefault();
        var id = $(this).data('id');
        $('.related_courses_content_' + id).toggleClass('hidden');

        if ($('.related_courses_content_' + id).is(":visible")) {
            $(this).text(t('HIDE_RELATED_COURSES'));
        } else {
            $(this).text(t('SEE_RELATED_COURSES'));
        }

    });

//SOLVE QUIZ

   
    var quiz_id = $('.quiz-name').attr('id');
    var course_id = $('.quiz-name').attr('course_id');
    var lesson_id = $('.quiz-name').attr('lesson_id');
	
    if (quiz_id !== undefined && course_id !== undefined &&  lesson_id !== undefined) {
    var info = '_'+quiz_id+'_'+course_id+'_'+lesson_id;

         //if localSrorage is empty then create object with questions id and answers id
        //else get localStarage content
        if (localStorage.getItem('answers'+info)) {
            var setAnswers = JSON.parse(localStorage.getItem('answers'+info));
        } else {
            var setAnswers = {};
        }
    
        var question_id = 0;
        $.each($('.solve-answers').parents('.question_id'), function (index, value) {
            question_id = $(value).attr('id');
    
            //if localstorage is empty
            if (!localStorage.getItem('answers'+info)) {
                setAnswers[question_id] = {'0':'0'};
            }
    
        });
        localStorage.setItem('answers'+info, JSON.stringify(setAnswers));
    
    
         $(document).on('change', '.solve-answers', function (e) {
            var question_id = $(this).parents('.question_id').attr('id');
            var answer_id = $(this).attr('id');
        //if checkbox is check make answers value in object = 1 /checked/
            if ($(this).is(":checked")) {
                setAnswers[question_id][answer_id] = 1;
								if (typeof(setAnswers[question_id][0]) !== 'undefined'){
										delete setAnswers[question_id][0];
								}
            } else {
                delete setAnswers[question_id][answer_id];
            }
    
            if ($(this).attr("type") == "radio") {
                $.each($(this).parents('.question_id').find("input"), function (index, value) {
                    delete setAnswers[question_id][$(value).attr('id')];
                });
            setAnswers[question_id][$(this).attr("id") ] = 1;
            }
        localStorage.setItem('answers'+info, JSON.stringify(setAnswers));
    
        });
    
        var answerObj = JSON.parse(localStorage.getItem('answers'+info));
        if (answerObj !== null) {
            $.each(answerObj, function (index, value) {
                $.each(value, function (index, value) {
                    if (value == 1) {
                        $('#' + index).attr('checked', true);
                    }
                });
            });
        }
    
        if (!localStorage.getItem('open_answers'+info)) {
            var openQuestionsObj = {};
            //if localstorage is empty add answers id and make their value "" /empty/
            //grop questions by type
            openQuestionsObj['open'] = {}
            openQuestionsObj['open-choices'] = {}
            $.each($('textarea.solve-open[question-type=open]'), function (index, value) {
                    openQuestionsObj['open'][$(value).parent().attr('id')] = "";
                });
            $.each($('textarea.solve-open[question-type=open-choices]'), function (index, value) {
                    openQuestionsObj['open-choices'][$(value).parent().attr('id')] = "";
                });
    
            localStorage.setItem('open_answers'+info, JSON.stringify(openQuestionsObj));
        } else {
          var openQuestionsObj = JSON.parse(localStorage.getItem('open_answers'+info));
        }
    
        //when text in textarea is changed
        $(document).on('input', '.solve-open', function () {
            var question_type = $(this).attr('question-type');
            var question_id = $(this).parent().attr('id');
            //if localStorage is not empty get values from it and set them in array so we can update them
            if (localStorage.getItem('open_answers'+info)) {
                openQuestionsObj[question_type][question_id] = JSON.parse(localStorage.getItem('open_answers'+info));
            }
    
            openQuestionsObj[question_type][question_id] = $(this).val();
            localStorage.setItem('open_answers'+info, JSON.stringify(openQuestionsObj));
        });
        //put values from localStorage on screen
        var openAnswerObj = JSON.parse(localStorage.getItem('open_answers'+info));
        if (openAnswerObj != null) {
            var textarea = $('textarea.solve-open');
            $.each(openAnswerObj, function (index, value) {
                $.each(value, function (index, value) {
                    textarea.parent("#" + index).children().val(value);
                });
            });
        }
    
    }

     $(document).on('click', '#start-quiz', function (e){
        e.preventDefault();
        if (ConfirmAlert(t('ARE_YOU_SHURE?', 0, false))) {
            window.location.href = $(this).attr('href');
        }
    });

    $(document).on('click', '#finishQuiz', function (e) {
    e.preventDefault();
        
        if (ConfirmAlert(t('ARE_YOU_SHURE?', 0, false))) {
            $(this).prop('disabled',true);
            $.ajax({
                type: "POST",
                url: _root + '/academy/quiz/finish-quiz',
                data_type: 'JSON',
                data: {
                    id: quiz_id,
                    course_id: course_id,
                    lesson_id: lesson_id,
                    open_answers: JSON.parse(localStorage.getItem('open_answers'+info)),
                    answers: JSON.parse(localStorage.getItem('answers'+info))
                },
            }).done(function (resp) {
                localStorage.removeItem('open_answers'+info);
                localStorage.removeItem('answers'+info);
                location.href = _root + '/quiz/active/'
            });
        }
    });

    $(document).on('click', '.delete-course', function(e){
        e.preventDefault();
        if (ConfirmAlert(t('ARE_YOU_SHURE?', 0, false))) {
            $.ajax({
                    type: "POST",
                    url: _root + '/academy/quiz/delete-quiz-course',
                    data_type: 'JSON',
                    data: {
                        id: $(this).attr('data-id'),
                        lesson_id: $(this).attr('data-lesson-id'),
                        course_id: $(this).attr('data-course-id'),
                    },
                }).done(function (resp) {
                    quizesCrud.update();
                });
        }
    });

    if (typeof __timer !== 'undefined') {
       countdownTimer(__timer); 
    }
});

    function setDisableStatus() {
        $('input[id^=current_status_]').each(function () {
            var current = $(this);
            $('.' + $(this).attr('id')).each(function () {
                if ($(this).data('status') == current.val()) {
                    $(this).addClass('disable');
                }
            });
        });
    }

    function countdownTimer(time) {
        if (time != "tt:tt:tt") {
            if (time != 0) {
                var hms = time;   // your input string
                var a = hms.split(':'); // split it at the colons
                var seconds = (+a[0]) * 60 * 60 + (+a[1]) * 60 + (+a[2]);
           
                var count = seconds;
                var counter = setInterval(function () {
										if (count == 0) {
												sendAnswers(counter);
												return 0;
										}
										count = count - 1;
										var seconds = count % 60;
										var minutes = Math.floor(count / 60);
										var hours = Math.floor(minutes / 60);
										minutes %= 60;
										hours %= 60;
														
										document.getElementById("timer").innerHTML = hours + " : " + minutes + " : " + seconds;
								}, 1000);
            }else{
                sendAnswers(counter);
                
            }
        }
        return;
    }
   

    function sendAnswers(counter) {
        var quiz_id = $('.quiz-name').attr('id');
        var course_id = $('.quiz-name').attr('course_id');
        var lesson_id = $('.quiz-name').attr('lesson_id');
        clearInterval(counter);
            if (quiz_id != undefined && course_id != undefined &&  lesson_id != undefined) {
                var info = '_'+quiz_id+'_'+course_id+'_'+lesson_id;
                $.ajax({
                    type: "POST",
                    url: _root + '/academy/quiz/finish-quiz',
                    data_type: 'JSON',
                    data: {
                        id: quiz_id,
                        course_id: course_id,
                        lesson_id: lesson_id,
                        open_answers: JSON.parse(localStorage.getItem('open_answers'+info)),
                        answers: JSON.parse(localStorage.getItem('answers'+info))
                    },
                }).done(function (resp){
                    localStorage.removeItem('open_answers'+info);
                    localStorage.removeItem('answers'+info);
                    location.href = _root + '/quiz/active/'
                });
                return;
            }
    }

		function maxElement(selector,attribute) {
      var min=null, max=null; var id = 0;
      $(selector).each(function() {
				var attr = $(this).attr(attribute);
				if (typeof attr !== typeof undefined && attr !== false) {
						 var id = parseInt(attr);
						if ((max===null) || (id > max)) { max = id; }
				}else{
						id = 1;
						max = id;
				}
      });
      return max;
    }

    function removeFromArray(arr) {
    var what, a = arguments, L = a.length, ax;
    while (L > 1 && arr.length) {
        what = a[--L];
        while ((ax= arr.indexOf(what)) !== -1) {
            arr.splice(ax, 1);
        }
    }
    return arr;
}
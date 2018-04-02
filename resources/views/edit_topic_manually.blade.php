@php
    function getSubstringArray($needle, $text){
        $lastPos    = 0;
        $array      = [];
        while (($lastPos = strpos($text, $needle, $lastPos)) !== false){
            $array[] = $lastPos;
            $lastPos = $lastPos + strlen($needle);
        }
        return $array;
    }
    $xml_path           = $xmlFilePath[0];
    $xml_path           = substr($xml_path, strpos($xml_path, '/') + 1);
    $text               = file_get_contents("storage/".$xml_path);
    $title_opening      = [];
    $title_closing      = [];
    $title_content      = [];
    $subtitle_opening   = [];
    $subtitle_closing   = [];
    $subtitle_content   = [];
    $paragraph_opening  = [];
    $paragraph_closing  = [];
    $paragraph_content  = [];
    $code_opening       = [];
    $code_closing       = [];
    $code_content       = [];

    $title_opening          = getSubstringArray('<titulo>', $text);
    $title_closing          = getSubstringArray('</titulo>', $text);
    $subtitle_opening       = getSubstringArray('<subtitulo>', $text);
    $subtitle_closing       = getSubstringArray('</subtitulo>', $text);
    $paragraph_opening      = getSubstringArray('<parrafo>', $text);
    $paragraph_closing      = getSubstringArray('</parrafo>', $text);
    $code_opening           = getSubstringArray('<codigo>', $text);
    $code_closing           = getSubstringArray('</codigo>', $text);
    for($i = 0; $i < count($title_opening); $i++){
        $title_content[$i] = substr($text, $title_opening[$i] + strlen('<titulo>'), $title_closing[$i] - $title_opening[$i] - strlen('</titulo>') + 1);
    }
    for($i = 0; $i < count($subtitle_opening); $i++){
        $subtitle_content[$i] = substr($text, $subtitle_opening[$i] + strlen('<subtitulo>'), $subtitle_closing[$i] - $subtitle_opening[$i] - strlen('</subtitulo>') + 1);
    }
    for($i = 0; $i < count($paragraph_opening); $i++){
        $paragraph_content[$i] = substr($text, $paragraph_opening[$i] + strlen('<parrafo>'), $paragraph_closing[$i] - $paragraph_opening[$i] -  strlen('</parrafo>') + 1);
    }
    for($i = 0; $i < count($code_opening); $i++){
        $code_content[$i] = substr($text, $code_opening[$i] + strlen('<codigo>'), $code_closing[$i] - $code_opening[$i] -  strlen('</codigo>') + 1);
    }
    $indexOpeningContent    = array_merge($title_opening, $subtitle_opening, $paragraph_opening, $code_opening);
    sort($indexOpeningContent);
    $titles        = 0;
    $subtitles     = 0;
    $paragraphs    = 0;
    $codes         = 0;
@endphp
@extends('layouts.app')
@section('title', 'Edición.')
@section('statics-css')
    @include('layouts/statics-css-1')
    <link rel="stylesheet" href="{{ URL::asset('/css/codemirror.css')}}"  type="text/css" />
    <link rel="stylesheet" href="{{ URL::asset('/css/monokai.css')}}"  type="text/css" />
    <script src="{{ URL::asset('/js/codemirror.js')}}"></script>
    <script src="{{ URL::asset('/js/matchbrackets.js')}}"></script>
    <script src="{{ URL::asset('/js/closebrackets.js')}}"></script>
    <script src="{{ URL::asset('/js/javascript.js')}}"></script>
    <script src="{{ URL::asset('/js/sublime.js')}}"></script>
    <link href="http://netdna.bootstrapcdn.com/bootstrap/3.3.5/css/bootstrap.css" rel="stylesheet">
    <link href="http://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.9/summernote.css" rel="stylesheet">
    <style>
        body{
            overflow-x: hidden;
        }
    </style>
@endsection
@section('menu')
    @include('layouts/menu', ['page' => 'category'])
@endsection
@section('content')
    <section class="section-less-padding" style="color:black;">
        <div class="container">
            <h3>{{$topic_name}} / Teoría</h3>
            <br>
            <div class="row" id="theory">
                <div class="col-md-12">
                    @foreach($indexOpeningContent as $key => $value)
                            @if(in_array($value, $title_opening))
                                <div class="col-md-12">
                                    <h3>Titulo</h3>
                                    <div class="input_holder">
                                        <input class="email_input" style="color:black;border-color:black;" type="search" name="title" value="{{$title_content[$titles++]}}">
                                    </div>
                                </div>
                            @endif
                            @if(in_array($value, $subtitle_opening))
                                <div class="col-md-12 margin-top3">
                                    <h3>Subtitulo</h3>
                                    <div class="input_holder">
                                        <input class="email_input" style="color:black;border-color:black;" type="search" value="{{$subtitle_content[$subtitles]}}" name="subtitle_{{++$subtitles}}">
                                    </div>
                                </div>
                                @if($key > 2)
                                    <script>
                                        setTimeout(function () {
                                            elements.push('subtitle');
                                        }, 1000);
                                    </script>
                                @endif
                            @endif
                            @if(in_array($value, $paragraph_opening))
                                <div class="col-md-12 margin-top3" style="width:87%;">
                                    <h3>Parrafo</h3>
                                    <div id="paragraph_{{++$paragraphs}}" ></div>
                                </div>
                                <script>
                                    setTimeout(function () {
                                        var string = <?php echo json_encode($paragraph_content[$paragraphs - 1]) ?>;
                                        $("#paragraph_{{$paragraphs}}").summernote("code", string);
                                    }, 1000);
                                </script>
                                @if($key > 2)
                                    <script>
                                        setTimeout(function () {
                                            elements.push('paragraph');
                                        }, 1000);
                                    </script>
                                @endif
                            @endif
                            @if(in_array($value, $code_opening))
                                <div class="col-md-12 margin-top3">
                                    <h3> Código</h3>
                                    <div id="code_{{++$codes}}" style="width:87%;height:auto; line-height:1px;"></div>
                                </div>
                                <script>
                                    setTimeout(function () {
                                        var string = <?php echo json_encode($code_content[$codes - 1]) ?>;
                                        editors[{{$codes}}].setValue(string);
                                    }, 1000);
                                </script>
                                @if($key > 2)
                                    <script>
                                        setTimeout(function () {
                                            elements.push('code');
                                        }, 1000);
                                    </script>
                                @endif
                            @endif
                    @endforeach
                </div>
            </div>
        </div>
        <div class="clearfix">
        <div class="row">
            <div class="col-md-12">
                <button class="btn btn-light" style="margin-top:30px;margin-left:50px;" id="addSubtitle">Agregar nuevo subtitulo</button>
                <button class="btn btn-light" style="margin-top:30px;margin-left:50px;" id="addParagraph">Agregar nuevo parrafo</button>
                <button class="btn btn-light" style="margin-top:30px;margin-left:50px;" id="addCode">Agregar código</button>
                <form action="{{url('creator/topic/theory/update/manually')}}" method="POST" id="finish">
                    {{ csrf_field() }}
                    <input type="submit" class="btn btn-success" style="margin-top:30px;margin-left:50px;"  value="Actualizar teoría" />
                    <input type="hidden" value="{{$topic_name}}" id="hiddenTopicName">
                </form>
            </div>
        </div>
    </section>
@endsection
@section('footer')
    @include('layouts/footer')
@endsection
@section('statics-js')
    @include('layouts/statics-js-1')
    <script src="/ace-builds/src-noconflict/ace.js" type="text/javascript" charset="utf-8"></script>
    <script src="http://cdnjs.cloudflare.com/ajax/libs/jquery/3.2.1/jquery.js"></script>
    <script src="http://netdna.bootstrapcdn.com/bootstrap/3.3.5/js/bootstrap.js"></script>
    <script src="http://cdnjs.cloudflare.com/ajax/libs/summernote/0.8.9/summernote.js"></script>
    <script src="{{ URL::asset('/js/summernote-es-ES.js')}}"></script>
    <script>
        var title       = 0;
        var subtitle    = 0;
        var paragraph   = 0;
        var code        = 0;
        var elements = ['title', 'subtitle', 'paragraph'];
        var editors     = [];
        $(document).ready(function() {
            title       = <?php echo json_decode($titles);      ?>;
            subtitle    = <?php echo json_decode($subtitles);   ?>;
            paragraph   = <?php echo json_decode($paragraphs);  ?>;
            code        = <?php echo json_decode($codes);       ?>;
            for(var i = 1; i <= code; i++){
                editors[i] = CodeMirror(document.getElementById("code_"+(i)), {
                    lineNumbers: true,
                    mode: "javascript",
                    keyMap: "sublime",
                    autoCloseBrackets: true,
                    matchBrackets: true,
                    showCursorWhenSelecting: true,
                    theme: "monokai",
                    tabSize: 2
                });
            }
            for(var j = 1; j <= paragraph; j++){
                $('#paragraph_'+ j).summernote({
                    lang: "es-ES",
                    toolbar: [
                        ['style', ['bold', 'italic', 'underline', 'clear']],
                        ['font', ['strikethrough', 'superscript', 'subscript']],
                        ['fontsize', ['fontsize']],
                        ['color', ['color']],
                        ['para', ['ul', 'ol', 'paragraph']],
                        ['height', ['height']],
                        ['picture',['picture']],
                        ['link',['link']],
                        ['video',['video']]
                    ],
                    placeholder: 'Introduce tu párrafo',
                    tabsize: 2,
                    height: 200,
                });
            }
        });
        $("#addSubtitle").click(function() {
            var elm = '<div class="col-md-12 margin-top3">\n' +
                '                    <h3>Subtitulo</h3>\n' +
                '                    <div class="input_holder">\n' +
                '                        <input class="email_input" style="color:black;border-color:black;" type="search" name="subtitle_'+(++subtitle)+'">\n' +
                '                    </div>\n' +
                '                </div>';
            $(elm).hide().appendTo('#theory').fadeIn();
            elements.push('subtitle');
        });
        $("#addParagraph").click(function() {
            var elm = '<div class="col-md-12 margin-top3" style="width:87%;">\n' +
                '                    <h3>Parrafo</h3>\n' +
                '                    <div id="paragraph_'+(paragraph + 1)+'"></div>' +
                '      </div>';

            $(elm).hide().appendTo('#theory').fadeIn();
            $('#paragraph_'+(++paragraph)).summernote({
                lang: "es-ES",
                toolbar: [
                    ['style', ['bold', 'italic', 'underline', 'clear']],
                    ['font', ['strikethrough', 'superscript', 'subscript']],
                    ['fontsize', ['fontsize']],
                    ['color', ['color']],
                    ['para', ['ul', 'ol', 'paragraph']],
                    ['height', ['height']],
                    ['picture',['picture']],
                    ['link',['link']],
                    ['video',['video']]
                ],
                placeholder: 'Introduce tu párrafo',
                tabsize: 2,
                height: 200,
            });
            elements.push('paragraph');
        });

        $("#addCode").click(function() {
            var elm =   '<div class="col-md-12 margin-top3">\n'   +
                '<h3> Código</h3>' +
                '<div id="code_'+(code + 1)+'" style="width:87%;height:auto; line-height:1px;">' +
                '</div>';
            $(elm).hide().appendTo('#theory').fadeIn();
            editors[++code] = CodeMirror(document.getElementById("code_"+(code)), {
                lineNumbers: true,
                mode: "javascript",
                keyMap: "sublime",
                autoCloseBrackets: true,
                matchBrackets: true,
                showCursorWhenSelecting: true,
                theme: "monokai",
                tabSize: 2
            });
            elements.push('code');
        });


        $("#finish").submit(function(e) {
            e.preventDefault();
            var p = 0;
            var c = 0;
            var t = 0;
            var s = 0;
            var xmlContent = "<teoria>";
            for(let i = 0; i < elements.length; i++) {
                if (elements[i] == 'title') {
                    xmlContent += '<titulo>\n';
                    xmlContent += $('input[name=title]').val();
                    xmlContent += '</titulo>\n'
                }
                if (elements[i] == 'subtitle') {
                    xmlContent += '<subtitulo>\n';
                    xmlContent += $('input[name=subtitle_' + (++s) + ']').val() + '\n';
                    xmlContent += '</subtitulo>\n'
                }
                if (elements[i] == 'paragraph') {
                    xmlContent += '<parrafo>\n';
                    xmlContent += $('#paragraph_'+(++p)).summernote('code') + '\n';
                    xmlContent += '</parrafo>\n'
                }
                if (elements[i] == 'code') {
                    xmlContent += '<codigo>\n';
                    xmlContent += editors[++c].getValue();
                    xmlContent += '</codigo>\n'
                }
            }
            xmlContent += "</teoria>";
            var url = $('#finish').attr('action');
            var topic_name = $('#hiddenTopicName').val();
            $.ajax({
                beforeSend: function(xhr){xhr.setRequestHeader('X-CSRF-TOKEN', $("#token").attr('content'));},
                url: url,
                type: 'POST',
                data: {"xmlContent": xmlContent, "topic_name": topic_name},
                dataType: 'json',
                success: function( _response ){
                    window.location.href = "/creator/topics";
                },
                error: function(xhr, status, error) {
                    alert(error);
                },
            });
            return 0;
        });

    </script>
@endsection





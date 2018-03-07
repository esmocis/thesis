@php
    $xmlstring      = file_get_contents('storage/'.$category_name.'/'.$topic_name.'/Cuestionario/cuestionario.xml');
    $try_number     = $tries;
    $xml            = simplexml_load_string($xmlstring);
    $questions      = [];
    $feedbacks      = [];
    $simulations    = [];
    $options        = [];
    $right_answers  = [];
    $i = 0;
    if($tries < $xml['cuestionarios']){
        foreach($xml->children()[$tries] as $index => $bloque) {
            array_push($questions, $bloque -> pregunta);
            array_push($feedbacks, $bloque -> retroalimentacion);
            array_push($simulations, $bloque -> simulacion);
            $option_list = [];
            $j = 0;
            foreach($bloque -> opcion  as $option){
                array_push($option_list, $option);
                if($option['value'] == 'true'){
                    $right_answers[$i] = $j + 1;
                }
                ++$j;
            }
            array_push($options, $option_list);
            ++$i;
        }
    }
@endphp
@extends('layouts.app')
@section('title', 'Cuestionario')
@section('statics-css')
    @include('layouts/statics-css-2')
    <link rel="stylesheet" type="text/css" href="/js/smart-forms/smart-forms.css">
    <link rel="stylesheet" href="/js/masterslider/style/masterslider.css" />
    <link href="/js/owl-carousel/owl.carousel.css" rel="stylesheet">
    <script src="{{ asset('/js/cytoscape.js')}}"></script>
@endsection
@section('menu')
    @include('layouts/menu', ['page' => 'category'])
@endsection
@section('content')
    <center><h5 class="uppercase weight3 pull">{{$topic_name}}</h5></center>
    <div class="carousel_holder">
        <div id="owl-demo7" class="owl-carousel" style="min-height:490px;">
            @foreach($questions as $key => $question)
                <div class="item">
                    <div class="row">
                        <div class="col-md-6" style="height:450px;">
                            <div class="row" style="height:100px;margin:30px;">
                                <h4> {{$question}} </h4>
                            </div>
                            <div class="row" style="margin-top:50px;margin-left:100px;">
                                <div class="col">
                                    @foreach($options[$key] as $secondKey => $option)
                                        <div class="form-check" id="{{$key}}">
                                            <input class="form-check-input" type="radio" name="options_{{($key + 1)}}" value="{{($secondKey + 1)}}" {{$secondKey == 0 ? 'checked': ''}}>
                                            <label class="form-check-label" for="options_{{($key + 1)}}">
                                                <h5>{{$option}}</h5>
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 bmargin">
                            <div id="questionnaire_{{($key + 1)}}" style="width:400px;height:400px;"></div>
                            <input type="hidden" id="json_{{($key + 1)}}" value="{{$simulations[$key]}}" />
                        </div>
                        <div class="row">
                            @if($key + 1 == count($questions))
                                <form action="{{url('/questionnaire/'.$topic_name.'/evaluate')}}" method="POST" id="questionnaireDone">
                                    <input type="hidden" id="numberOfQuestions" value="{{count($questions)}}">
                                    <input type="hidden" id="hiddenTopicName" value="{{$topic_name}}">
                                    <input type="submit" class="btn btn-success" style="float:right; margin-right:100px;" value="Terminar cuestionario" />
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
            @if(count($questions) == 0)
                    <div class="row" style="margin-top:200px;">
                        <div class="col-md-6 text-center" style="float: none; margin: 0 auto;">
                            <h4>Has agotado los intentos para este tema, vuelve más tarde.</h4>
                        </div>
                    </div>
            @endif
        </div>
    </div>
    <form action="{{url('/questionnaire/answers')}}" method="POST" id="getAnswers">
        {{ csrf_field() }}
    </form>
@endsection

@section('footer')
    @include('layouts/footer')
@endsection

@section('statics-js')
    @include('layouts/statics-js-2')
    <script src="{{ asset('/js/questionnaire_simulation_provider.js')}}"></script>
    <script src="{{ asset('/js/owl-carousel/owl.carousel.js')}}"></script>
    <script src="{{ asset('/js/owl-carousel/custom.js')}}"></script>
    <script>
        var answers = [];
        $(document).ready(function(){
            $("#getAnswers").submit(function(e) {
                var url = $('#getAnswers').attr('action');
                var topic_name = <?php echo json_encode($topic_name); ?>;
                var tries = <?php echo json_encode($tries); ?>;
                $.ajax({
                    beforeSend: function(xhr){xhr.setRequestHeader('X-CSRF-TOKEN', $("#token").attr('content'));},
                    url: url,
                    type: 'POST',
                    data: {"topic_name": topic_name, "tries": tries},
                    dataType: 'json',
                    success: function( _response ){
                        answers = _response.success;
                    },
                    error: function(xhr, status, error) {
                        alert(error);
                    },
                });
                e.preventDefault();
                return 0;
            });
            $('#getAnswers').submit();
        });
        $("#questionnaireDone").submit(function(e) {
            var user_answers = [];
            var number_of_questions = $('#numberOfQuestions').val();
            for(var i = 0; i < number_of_questions; i++)
                user_answers.push($('input[name="options_'+(i+1)+'"]:checked').val());
            var url = $('#questionnaireDone').attr('action');
            var topic_name = $('#hiddenTopicName').val();
            var right_answers = answers;
            $.ajax({
                beforeSend: function(xhr){xhr.setRequestHeader('X-CSRF-TOKEN', $("#token").attr('content'));},
                url: url,
                type: 'POST',
                data: {"user_answers": user_answers, "topic_name": topic_name, "right_answers": right_answers},
                dataType: 'json',
                success: function( _response ){
                    location.reload();
                },
                error: function(xhr, status, error) {
                    alert(error);
                },
            });
            e.preventDefault();
            return 0;
        });
    </script>
@endsection

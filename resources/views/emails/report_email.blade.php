<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">

    <title>Report Alarm Assistant</title>
  </head>
  <body>
    <h1>Report Alarm Assistant</h1>

    <div class="row">
        <div class="col-md-6">
          <h6>Customer / Owner</h6>
        </div>
        <div class="col-md-6">
          <span>
            {{$getData->customer_name}}
          </span>
        </div>

        <div class="col-md-6">
          <h6>Room</h6>
        </div>
        <div class="col-md-6">
          <span>
            {{$getData->room_name}}
          </span>
        </div>

        <div class="col-md-6">
          <h6>Date & Time</h6>
        </div>
        <div class="col-md-6">
          <span>
            {{$getData->date}}, {{$getData->time}}
          </span>
        </div>

        <div class="col-md-6">
          <h6>Alarm Type</h6>
        </div>
        <div class="col-md-6">
          <span>
            {{$getData->alarm_type}}
          </span>
        </div>

        <div class="col-md-6">
          <h6>City</h6>
        </div>
        <div class="col-md-6">
          <span>
            {{$getData->city}}
          </span>
        </div>

        <div class="col-md-6">
          <h6>Address</h6>
        </div>
        <div class="col-md-6">
          <span>
            {{$getData->address}}
          </span>
        </div>

        <div class="col-md-6">
          <h6>Office Comments</h6>
        </div>
        <div class="col-md-6">
          <span>
            {{$getData->comments}}
          </span>
        </div>

      </div>
    @if($getData->alarm_type == 'owner_call' || $getData->alarm_type == 'neighbor_call' || $getData->alarm_type == 'guest_call' )
      <div class="row" >
        <div class="col-md-6">
          <h6>Caller Name</h6>
        </div>
        <div class="col-md-6">
          <span>
            {{$getData->caller_name}}
          </span>
        </div>

        <div class="col-md-6">
          <h6>Caller Phone Number</h6>
        </div>
        <div class="col-md-6">
          <span>
            {{$getData->caller_phone_number}}
          </span>
        </div>

        <div class="col-md-6">
          <h6>Caller Location</h6>
        </div>
        <div class="col-md-6">
          <span>
            {{$getData->caller_location}}
          </span>
        </div>

      </div>
      @endif

      <div class="row">
        <div class="col-md-6"><h6> Night Agent</h6></div>
        <div class="col-md-6"><span>{{($getData->agent_id == 0) ? 'N/A' :$getData->agent_name }}</span></div>
        
        <div class="col-md-6"><h6> Intervention Time</h6></div>
        <div class="col-md-6"><span>{{$getData->intervention_time}}</span></div>

        <div class="col-md-6"><h6> Intervention Duration</h6></div>
        <div class="col-md-6"><span>{{$getData->intervention_duration}} minutes</span></div>

        <div class="col-md-6"><h6> Guest opened the door?</h6></div>
        <div class="col-md-6"><span>{{($getData->is_guest_opened_door == 1 ) ? 'Yes' : 'No'}}</span></div>

        <div class="col-md-6"><h6> Noise goes down?</h6></div>
        <div class="col-md-6"><span>{{($getData->is_noise_goes_down == 1 ) ? 'Yes' : 'No'}}</span></div>

        <div class="col-md-6"><h6> Noise heard from outside?</h6></div>
        <div class="col-md-6"><span>{{($getData->is_noise_heard_from_outside == 1 ) ? 'Yes' : 'No'}}</span></div>

        <div class="col-md-6"><h6> Night Agent Comments</h6></div>
        <div class="col-md-6"><span>{{$getData->agent_comments}}</span></div>

      </div>

    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.14.7/dist/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
  </body>
</html>
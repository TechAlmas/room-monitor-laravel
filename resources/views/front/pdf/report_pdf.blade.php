
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

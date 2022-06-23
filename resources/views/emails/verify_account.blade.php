<table border="0" cellpadding="0" cellspacing="0"  width="100%">
    <tbody>
        <tr>
            <td align="center" style="background-color: rgb(241, 241, 241); padding-left: 8px; padding-right: 8px;">
                <table  border="0" cellpadding="0" cellspacing="0" width="100%">
                    <tbody>
                        <tr>
                            <td align="left">
                                <h4 style="font-weight: bold; line-height: 23px; margin-top: 0px; margin-bottom: 8px;">Hi {{$name}}</h4>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Your account has been created successfully. <br>
                                Please click <a href="{{$verifyLink}}">here</a> to verify your account.
                            </td>
                        </tr>
                        <tr>
                            <td align="left">
                                Your login details:- <br>
                                Email    :- {{$email}} <br>
                                Username :- {{$username}} <br>
                                Password :- {{$password}} <br>

                            </td>
                        </tr>
                    </tbody>

                </table>
            </td>
        </tr>
    </tbody>
</table>

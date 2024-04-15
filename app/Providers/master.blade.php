<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description"
          content="viho admin is super flexible, powerful, clean &amp; modern responsive bootstrap 4 admin template with unlimited possibilities. laravel/framework: ^8.40">
    <meta name="keywords"
          content="admin template, viho admin template, dashboard template, flat admin template, responsive admin template, web app">
    <meta name="author" content="pixelstrap">
    <link rel="icon" href="{{asset('assets/images/favicon.png')}}" type="image/x-icon">
    <link rel="shortcut icon" href="{{asset('assets/images/favicon.png')}}" type="image/x-icon">
    <title>@yield('title')</title>
    <!-- Google font-->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Montserrat:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&amp;display=swap"
        rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&amp;display=swap"
        rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Rubik:ital,wght@0,400;0,500;0,600;0,700;0,800;0,900;1,300;1,400;1,500;1,600;1,700;1,800;1,900&amp;display=swap"
        rel="stylesheet">
    <!-- Font Awesome-->
    @includeIf('layouts.admin.partials.css')
</head>
<body>
<!-- Loader starts-->
<div class="loader-wrapper">
    <div class="theme-loader"></div>
</div>
<!-- Loader ends-->
<!-- page-wrapper Start-->
<div class="page-wrapper compact-sidebar" id="pageWrapper">
    <!-- Page Header Start-->

    @includeIf('layouts.admin.partials.header')

<!-- Page Header Ends -->
    <!-- Page Body Start-->
    <div class="page-body-wrapper sidebar-icon">
        <!-- Page Sidebar Start-->
    @auth
        @includeIf('layouts.admin.partials.sidebar')
    @endauth
    <!-- Page Sidebar Ends-->
        <div class="page-body">
            <!-- Container-fluid starts-->
        @yield('content')
        <!-- Container-fluid Ends-->
        </div>
        <!-- footer start-->
        <footer class="footer">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6 footer-copyright">
                        <p class="mb-0">Copyright {{date('Y')}}-{{date('y', strtotime('+1 year'))}} © Zaptrance Worldwide Systems LTD.</p>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</div>

<div class="modal fade" id="updatePasswordModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Add/Update User</h5>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if($errors->has('password'))
                    <div class="alert alert-danger">
                        {{ $errors->first('password') }}
                    </div>
                @endif
                @if($errors->has('password_message'))
                    <div class="alert alert-success">
                        {{ $errors->first('password_message') }}
                    </div>
                @endif
                <form class="needs-validation" novalidate="" method="post" action="{{route('admin.user.password.change')}}" id="updatePasswordForm">
                    @csrf
                    <div class="card-body card-padding">
                        <div class="row">
                            <div class="col-sm-12">
                                <div class="input-group">
                                    <span class="input-group-addon"><i class="zmdi zmdi-lock"></i></span>
                                    <div class="fg-line">
                                        <input type="password" class="form-control" placeholder="Old Password"
                                               name="old_password" id="password" required>
                                    </div>
                                </div>

                                <br>

                                <div class="input-group">
                                    <span class="input-group-addon"><i class="zmdi zmdi-lock-outline"></i></span>
                                    <div class="fg-line">
                                        <input type="password" class="form-control" placeholder="New Password"
                                               name="password" required>
                                    </div>
                                </div>


                                <br>

                                <div class="input-group">
                                    <span class="input-group-addon"><i class="zmdi zmdi-lock-outline"></i></span>
                                    <div class="fg-line">
                                        <input type="text" class="form-control" placeholder="New Password Again"
                                               name="password_confirmation">
                                    </div>
                                </div>

                            </div>
                        </div>


                    </div>
                </form>


            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" type="submit" id="updatePasswordBtn">Save</button>
                <button class="btn btn-primary" type="button" data-bs-dismiss="modal">Close</button>
                {{--                    <button class="btn btn-secondary" type="button">Save changes</button>--}}
            </div>
        </div>
    </div>
</div>

<!-- latest jquery-->
@includeIf('layouts.admin.partials.js')
<script>
    $(function () {
        var passwordModal = new bootstrap.Modal(document.getElementById('updatePasswordModal'), {
            keyboard: false
        });
        @if($errors->has('password') || $errors->has('password_message'))
        passwordModal.show();
        @endif
        $("#updatePasswordBtn").on('click', function () {
            $("#updatePasswordForm").submit();
        });
        $('#showUpdateModalForm').on('click', function (e) {
            e.preventDefault();
            passwordModal.show();
        });
    })
</script>
</body>
</html>

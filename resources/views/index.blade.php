@extends('seleniumidemanager::layouts.master')
@section('content')
    <div
        class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Suites manager</h1>
    </div>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
            <tr>
                <th>#</th>
                <th>Suite name</th>
                <th>Suite color</th>
                <th>Total test case</th>
                <th>Status</th>
                <th>Created at</th>
                <th>Edit</th>
                <th>Delete</th>
            </tr>
            </thead>
            <tbody>
            @if(!empty($data))
                @foreach($data as $index => $item)
                    <tr>
                        <td>{{$index+1}}</td>
                        <td>
                            <a href="{{$item['ide_file_path']}}" title="Download file .site" download>
                                <span data-feather="download"></span>
                            </a>
                            {{$item['name']}}
                        </td>
                        <td>
                            <div class="colorSelector" data-hex="{{$item['hex_color']}}">
                                <div style="background-color: #{{$item['hex_color']}}"></div>
                            </div>
                            <a data-id="{{$item['id']}}"
                               data-hex="{{$item['hex_color']}}"
                               style="cursor: pointer; display: none"
                               class="save-color text-primary">Save</a>
                        </td>
                        <td class="col-test-case">
                            @include('seleniumidemanager::test_case', ['testCases' => $item['test_cases'], 'suite' => $item])
                        </td>
                        <td>
                            <input data-id="{{$item['id']}}" class="switch-button" type="checkbox"
                                   {{$item['status'] == 1 ? 'checked' : ''}}
                                   data-toggle="toggle"
                                   data-size="xs"
                                   data-on="Enabled"
                                   data-off="Disabled"
                                   data-onstyle="success"
                                   data-offstyle="danger"
                            >
                        </td>
                        <td>{{$item['created_at']}}</td>
                        <td>
                            <a href="{{route('selenium-ide-manager.suite.create', ['id' => $item['id']])}}">
                                <span data-feather="upload"></span>
                            </a>
                        </td>
                        <td>
                            <form name="" method="POST" enctype="multipart/form-data"
                                  action="{{route('selenium-ide-manager.suite.destroy', ['id' => $item['id']])}}">
                                @method('delete')
                                @csrf
                                <span style="cursor: pointer" class="text-danger trash" data-feather="trash-2"></span>
                            </form>
                        </td>
                    </tr>
                @endforeach
            @endif
            </tbody>
        </table>
    </div>
@stop
@push('style')
    <link href="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/css/bootstrap4-toggle.min.css"
          rel="stylesheet">
    <link href="{{asset('vendor/plum/colorpicker/css/colorpicker.css')}}" rel="stylesheet">
    <style type="text/css">
        .colorSelector {
            position: relative;
            width: 36px;
            height: 36px;
            background: url({{asset('vendor/plum/colorpicker/images/select.png')}});
            display: inline-flex;
        }

        .colorSelector div {
            position: absolute;
            top: 3px;
            left: 3px;
            width: 30px;
            height: 30px;
            background: url({{asset('vendor/plum/colorpicker/images/select.png')}}) center;
        }
    </style>
@endpush
@push('script')
    <script src="https://cdn.jsdelivr.net/gh/gitbrent/bootstrap4-toggle@3.6.1/js/bootstrap4-toggle.min.js"></script>
    <script
        src="https://cdn.jsdelivr.net/npm/gasparesganga-jquery-loading-overlay@2.1.6/dist/loadingoverlay.min.js"></script>

    <script src="{{asset('vendor/plum/colorpicker/js/colorpicker.js')}}"></script>
    <script type="text/javascript">

        $(function () {
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            $('.colorSelector').ColorPicker({
                onBeforeShow: function () {
                    let hex = $(this).data('hex');
                    $(this).ColorPickerSetColor(hex);
                    $(this).find('div').css('backgroundColor', '#' + hex);
                },
                onShow: function (colpkr) {
                    $(colpkr).fadeIn(500);
                    return false;
                },
                onHide: function (colpkr) {
                    $(colpkr).fadeOut(500);
                    return false;
                },
                onChange: function (hsb, hex, rgb, el) {
                    $(el).find('div').css('backgroundColor', '#' + hex);
                    $(el).attr('data-hex', hex);
                    $(el).next().attr('data-hex', hex);
                    $(el).next().show();
                }
            });

            $('.save-color').click(function () {
                let hex = $(this).data('hex');
                let suiteId = $(this).data('id');
                let self = $(this);
                $.ajax({
                    type: 'POST',
                    url: `/selenium-ide-manager/test-case/change-color`,
                    data: {
                        id: suiteId,
                        hex_color: hex
                    },
                    success: function (data) {
                        console.log(data);
                        $(self).hide();
                    },
                    beforeSend: function () {
                        $.LoadingOverlay("show");
                    },
                    complete: function () {
                        $.LoadingOverlay("hide");
                    },
                })
            });

            $('.switch-button').change(function () {
                let suiteId = $(this).data('id');

                $.ajax({
                    type: 'GET',
                    url: `/selenium-ide-manager/suite/change-status/${suiteId}`,
                    success: function (data) {
                        console.log(data);
                    },
                    beforeSend: function () {
                        $.LoadingOverlay("show");
                    },
                    complete: function () {
                        $.LoadingOverlay("hide");
                    },
                })
            })

            $('.trash').click(function () {
                if (!confirm('Are you sure?')) {
                    return;
                }
                $(this).parents('form').submit();
            })

            $('.table-striped').on('click', '.test-case-item', function () {
                let suiteId = $(this).data('suite-id');
                let testCaseId = $(this).data('id');
                let self = $(this);
                $.ajax({
                    type: 'POST',
                    url: `/selenium-ide-manager/test-case/${testCaseId}`,
                    data: {
                        suite_id: suiteId,
                        _method: 'PUT'
                    },
                    success: function (data) {
                        $(self).parents('td.col-test-case').html(data);
                        feather.replace()
                    },
                    beforeSend: function () {
                        $.LoadingOverlay("show");
                    },
                    complete: function () {
                        $.LoadingOverlay("hide");
                    },
                })
            })
        })
    </script>
@endpush

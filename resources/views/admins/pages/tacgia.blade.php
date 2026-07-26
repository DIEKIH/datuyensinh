@extends('admins.layouts.app')

@section('title', 'Quản lý Tác giả')

@section('css')
    <link rel="stylesheet" href="/css/admins/baiviet.css">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-bs5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/css/bootstrap-datepicker.min.css"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        .form-control:focus {
            box-shadow: none;
            border-color: #ced4da;
        }
    </style>


@endsection

@section('content')

    <div class="container-fluid">
        <div class="page-header">
            <h1 class="page-title">Danh sách tác giả</h1>
        </div>

        <div class="row align-items-stretch">
            {{-- Form thêm tác giả --}}
            <div class="col-4 d-flex">
                <div class="card flex-fill" style="min-height: 100%">
                    <div class="card-header fw-bold">Thêm tác giả</div>
                    <div class="card-body">
                        <div class="form-group mb-3">
                            <label for="tacgia_ten" style="margin-bottom: 5px">Tên tác giả:</label>
                            <input type="text" class="form-control" id="tacgia_ten" style="height:28px">

                            <span class="search" id="err_tacgia_ten"
                                style="color:red;font-size:small;font-weight:light;position: absolute; top: 104px; right: 17px;"></span>
                        </div>
                        <div class="card-header" style="padding: 0;margin-left: 10px; margin-bottom: 10px;"></div>

                        <div class="row">
                            <div class="col-6">
                                <button type="button" id="tacgia_lammoi" onclick="tacgia_lammoi()"
                                    class="btn btn-outline-primary btn-sm w-100">
                                    <i class="fa-solid fa-rotate"></i> Làm mới
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" id="tacgia_them" onclick="tacgia_them()"
                                    class="btn btn-primary btn-sm w-100">
                                    <i class="fa-solid fa-floppy-disk"></i> Thêm
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Danh sách tác giả --}}
            <div class="col-8 d-flex">
                <div class="card flex-fill" style="min-height: 100%">
                    <div class="card-header fw-bold">Danh sách tác giả</div>
                    <div class="card-body">
                        <table id="tacgia_danhsach" class="table table-bordered table-hover table-striped w-100">
                            {{-- Nội dung được load qua JS --}}
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Modal cập nhật --}}
    <div class="modal" id="tacgia_capnhat_modal">
        <div class="modal-dialog modal-dialog-centered" style="max-width: 600px !important; width: 600px !important;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Cập nhật tác giả</h5>
                    <button type="button" class="btn-close" onclick="close_modal_tacgia()"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="update_tacgia_ten">Tên tác giả:</label>
                        <input type="text" id="update_tacgia_ten" class="form-control" style="height:28px">
                        <span class="search" id="err_update_tacgia_ten"
                            style="color:red;font-size:small;font-weight:light;position: absolute; top: 53px; right: 24px;"></span>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="btn_capnhat_tacgia" class="btn btn-primary btn-sm"
                        onclick="tacgia_capnhat()">
                        <i class="fa-solid fa-floppy-disk"></i> Cập nhật
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="tacgia_lammoi()">
                        <i class="fa-solid fa-rotate"></i> Làm mới
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="close_modal_tacgia()">
                        <i class="fa-regular fa-circle-xmark"></i> Hủy
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('js')
    <script src="{{ asset('js/admins/tacgia.js') }}"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/summernote-bs5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.9.0/dist/lang/summernote-vi-VN.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-datepicker/1.9.0/js/bootstrap-datepicker.min.js"></script>
@endsection

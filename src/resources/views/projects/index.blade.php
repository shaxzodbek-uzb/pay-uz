@extends('pay-uz::layouts.app')

@section('title')
    Loyihalar
@stop

@section('style')
    <style>

    </style>
@stop

@section('content')
    <div class="container-fluid pb-4">
        <!-- <div class="col-12 mb-4"> -->
        <div class="row mb-4">
            <div class="col-6">
                <span class="h5">Loyihalar</span>
            </div>
            <div class="col-6 text-right">
                <a href="{{ route('payment.projects.create') }}" class="btn btn-sm btn-primary" role="button"><span class="fa fa-plus"></span> Yangi qo'shish</a>
            </div>
        </div>
        <!-- </div> -->
        <div class="col-12 box-admin pt-3 pb-3">
            <div class="col-12 pb-2 mb-4" style="border-bottom: solid 1px; border-color: #eeeeee;">
                <div class="row">
                    <span class="text-topics h6">Loyihalar</span>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" id="dataTable" cellspacing="0">
                    <thead class="thead-default">
                    <tr>
                        <th>Name</th>
                        <th>System</th>
                        <th>Created at</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tfoot class="thead-default">
                    <tr>
                        <th>Name</th>
                        <th>System</th>
                        <th>Created at</th>
                        <th></th>
                    </tr>
                    </tfoot>
                    <tbody>
                    @foreach($projects as $project)
                        <tr class="@if($project->status == \Goodoneuz\PayUz\Models\Project::NOT_ACTIVE) table-danger @endif">
                            <td>{{ $project->name }}</td>
                            <td>{{ $project->status }}</td>
                            <td>{{ $project->created_at }}</td>
                            <td class="text-center">
                                <a href="#" data-project-id="{{ $project->id }}" class="deleteBtn"><span class="fa fa-trash" data-toggle="tooltip" data-placement="top" title="O'chirish"></span></a> &nbsp;
                                <a href="{{ route('payment.projects.edit',['project'  => $project->id]) }}"><span class="fa fa-cog" data-toggle="tooltip" data-placement="top" title="Sozlash"></span></a> &nbsp;
                                <a href="{{ route('payment.projects.edit_status',['project'  => $project->id]) }}"><span class="fa @if($project->status == \Goodoneuz\PayUz\Models\Project::NOT_ACTIVE) fa-lock @else fa-unlock-alt @endif " data-toggle="tooltip" data-placement="top" title="Bloklash"></span></a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Delete modal -->
    <div class="modal fade" id="modalDeleteProject" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="deleteProjectForm" method="post" action="">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="exampleModalLongTitle">Confirmation</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        Are you sure you want to remove it?
                        <input id="deleteSystemId" type="hidden" name="project_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@stop

@section('script')
    <script type="text/javascript">
        $('.deleteBtn').on('click', function () {
            $("#deleteProjectForm").attr('action','/payment/projects/'+$(this).data('project-id'));
            $("#modalDeleteProject").modal('show');
        });
    </script>
@stop

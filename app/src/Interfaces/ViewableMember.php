<?php


interface ViewableMember
{
    public function MyViewerGroups();

    public function HasDependentRecords(): bool;
}

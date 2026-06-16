awad.Client\Views\Shared\PartialViews\_EventsSidebarItem.cshtml
<li class="nav-item">
    <a asp-controller="Events" asp-action="Index" class="nav-link">
        <i class="nav-icon bi bi-calendar-event"></i>
        <p>Events</p>
        <i class="nav-arrow bi bi-chevron-right"></i>
    </a>
    <ul class="nav nav-treeview">
        <li class="nav-item">
            <a asp-controller="Events" asp-action="Index" class="nav-link">
                <i class="nav-icon bi bi-circle"></i>
                <p>Event List</p>
            </a>
        </li>
        <li class="nav-item">
            <a asp-controller="Events" asp-action="Create" class="nav-link">
                <i class="nav-icon bi bi-plus-circle"></i>
                <p>Create Event</p>
            </a>
        </li>
    </ul>
</li>
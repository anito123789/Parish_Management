<?php
require_once 'db.php';
include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-primary text-white shadow">
                    <div class="card-body py-4">
                        <h1 class="display-4">📖 System User Guide</h1>
                        <p class="lead">Everything you need to know about managing your parish with this system.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Sidebar Navigation for Guide -->
            <div class="col-lg-3">
                <div class="card shadow mb-4 sticky-top" style="top: 20px;">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold">Help Categories</h6>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="#intro" class="list-group-item list-group-item-action">🚀 Getting Started</a>
                        <a href="#families" class="list-group-item list-group-item-action">👨‍👩‍👧‍👦 Families &
                            Members</a>
                        <a href="#sacraments" class="list-group-item list-group-item-action">📜 Sacraments & Certs</a>
                        <a href="#reports" class="list-group-item list-group-item-action">🖨️ Reports & Letters</a>
                        <a href="#finances" class="list-group-item list-group-item-action">💰 Finances</a>
                        <a href="#planner" class="list-group-item list-group-item-action">📅 Planner</a>
                        <a href="#profile" class="list-group-item list-group-item-action">⛪ Settings & Backup</a>
                    </div>
                </div>
            </div>

            <!-- Content Area -->
            <div class="col-lg-9">
                <div class="card shadow mb-4">
                    <div class="card-body p-5">
                        <section id="intro" class="mb-5">
                            <h3>🚀 Getting Started</h3>
                            <hr>
                            <p>The **Dashboard** is your home screen. It shows you total statistics (Families,
                                Parishioners, Sacraments) and alerts you about today's birthdays and anniversaries.</p>
                        </section>

                        <section id="families" class="mb-5">
                            <h3>👨‍👩‍👧‍👦 Families & Parishioner Management</h3>
                            <hr>
                            <ul>
                                <li><strong>Adding a Family:</strong> Go to <code>Families</code> ->
                                    <code>Add New Family</code>. Enter the Head of the Family first.</li>
                                <li><strong>Adding Members:</strong> Open a Family view and click
                                    <code>Add Member</code> to register children, spouses, or elders.</li>
                                <li><strong>Search:</strong> Use the global search bar to find anyone instantly by Name
                                    or ID.</li>
                            </ul>
                        </section>

                        <section id="sacraments" class="mb-5">
                            <h3>📜 Sacraments & Certificates</h3>
                            <hr>
                            <p>Track Baptism, First Communion, Confirmation, Marriage, and Death records. Once a record
                                is saved, you can go to the <strong>Certificates Hub</strong> to print a professional A4
                                certificate.</p>
                        </section>

                        <section id="reports" class="mb-5">
                            <h3>🖨️ Reports & Official Letters</h3>
                            <hr>
                            <p>Generate Marriage Banns, Recommendation Letters, and statistical reports. Most reports
                                are optimized for landscape printing on A4 paper.</p>
                        </section>

                        <section id="finances" class="mb-5">
                            <h3>💰 Finances & Subscriptions</h3>
                            <hr>
                            <p>Each family has a subscription record. You can log payments here to keep the parish
                                treasury organized and transparent.</p>
                        </section>

                        <section id="planner" class="mb-5">
                            <h3>📅 Event Planner</h3>
                            <hr>
                            <p>Use the Calendar to schedule parish events. This ensures no meetings or choir practices
                                collide with important church activities.</p>
                        </section>

                        <section id="profile" class="mb-5">
                            <h3>⛪ Parish Settings & Backup</h3>
                            <hr>
                            <p>In the <strong>Profile</strong> menu, you can upload your Church Logo and name the Vicar.
                                <strong>CRITICAL:</strong> Use the <strong>Database Management</strong> menu once a week
                                to download a backup zip of your data for safety.</p>
                        </section>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    section h3 {
        color: #4e73df;
        font-weight: 700;
    }

    hr {
        margin-bottom: 1.5rem;
    }

    .list-group-item.active {
        background-color: #4e73df;
        border-color: #4e73df;
    }

    html {
        scroll-behavior: smooth;
    }
</style>

<?php include 'includes/footer.php'; ?>
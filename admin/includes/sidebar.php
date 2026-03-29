  <nav class="sidebar sidebar-offcanvas" id="sidebar">
      <?php
        // Get the current PHP file name
        $current_page = basename($_SERVER['PHP_SELF']);
        ?>
      <ul class="nav">
          <li class="nav-item <?php echo $current_page == 'index.php' ? 'active-link' : ''; ?>">
              <a class="nav-link <?php echo $current_page == 'index.php' ? 'active-link' : ''; ?>" href="index.php" <?php echo $current_page == 'index.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
                  <i class="mdi mdi-grid-large menu-icon" <?php echo $current_page == 'index.php' ? 'style="color: white !important;"' : ''; ?>></i>
                  <span class="menu-title" <?php echo $current_page == 'index.php' ? 'style="color: white !important;"' : ''; ?>>Index</span>
              </a>
          </li>


          <li class="nav-item <?php echo $current_page == 'academic_info.php' ? 'active-link' : ''; ?>">
              <a class="nav-link <?php echo $current_page == 'academic_info.php' ? 'active-link' : ''; ?>" href="academic_info.php" <?php echo $current_page == 'academic_info.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
                  <i class="menu-icon mdi mdi-school"
                      <?= $current_page == 'academic_info.php' ? 'style="color: white !important;"' : ''; ?>>
                  </i>
                  <span class="menu-title"
                      <?= $current_page == 'academic_info.php' ? 'style="color: white !important;"' : ''; ?>>
                      Academic Year
              </a>
          </li>

          <li class="nav-item <?php echo $current_page == 'academic_details.php' ? 'active-link' : ''; ?>">
              <a class="nav-link <?php echo $current_page == 'academic_details.php' ? 'active-link' : ''; ?>" href="academic_details.php" <?php echo $current_page == 'academic_details.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
                  <i class="menu-icon mdi mdi-information" <?php echo $current_page == 'academic_details.php' ? 'style="color: white !important;"' : ''; ?>></i>
                  <span class="menu-title" <?php echo $current_page == 'academic_details.php' ? 'style="color: white !important;"' : ''; ?>>Academic Info</span>
              </a>
          </li>
          <!-- <li class="nav-item <?php echo $current_page == 'voter-list.php' ? 'active-link' : ''; ?>">
              <a class="nav-link <?php echo $current_page == 'voter_custom_fields.php' ? 'active-link' : ''; ?>" href="voter_custom_fields.php" <?php echo $current_page == 'voter_custom_fields.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
                  <i class="menu-icon mdi mdi-format-list-bulleted-type" <?php echo $current_page == 'voter_custom_fields.php' ? 'style="color: white !important;"' : ''; ?>></i>
                  <span class="menu-title" <?php echo $current_page == 'voter_custom_fields.php' ? 'style="color: white !important;"' : ''; ?>>Custom Fields</span>
              </a>
          </li> -->
          <li class="nav-item <?php echo $current_page == 'voter-list.php' ? 'active-link' : ''; ?>">
              <a class="nav-link <?php echo $current_page == 'voter-list.php' ? 'active-link' : ''; ?>" href="voter-list.php" <?php echo $current_page == 'voter-list.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
                  <i class="menu-icon mdi mdi-account-group" <?php echo $current_page == 'voter-list.php' ? 'style="color: white !important;"' : ''; ?>></i>
                  <span class="menu-title" <?php echo $current_page == 'voter-list.php' ? 'style="color: white !important;"' : ''; ?>>Voter List</span>
              </a>
          </li>
          <li class="nav-item <?php echo $current_page == 'emails.php' ? 'active-link' : ''; ?>">
              <a class="nav-link <?php echo $current_page == 'emails.php' ? 'active-link' : ''; ?>" href="emails.php" <?php echo $current_page == 'emails.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
                  <i class="mdi mdi-email menu-icon" <?php echo $current_page == 'emails.php' ? 'style="color: white !important;"' : ''; ?>></i>
                  <span class="menu-title" <?php echo $current_page == 'emails.php' ? 'style="color: white !important;"' : ''; ?>>Emails</span>
              </a>
          </li>
          <li class="nav-item <?php echo $current_page == 'advisers.php' ? 'active-link' : ''; ?>">
              <a class="nav-link <?php echo $current_page == 'advisers.php' ? 'active-link' : ''; ?>" href="advisers.php" <?php echo $current_page == 'advisers.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
                  <i class="mdi mdi-account-tie menu-icon" <?php echo $current_page == 'advisers.php' ? 'style="color: white !important;"' : ''; ?>></i>
                  <span class="menu-title" <?php echo $current_page == 'advisers.php' ? 'style="color: white !important;"' : ''; ?>>Advisers</span>
              </a>
          </li>
          <li class="nav-item <?php echo $current_page == 'election.php' ? 'active-link' : ''; ?>">
              <a class="nav-link <?php echo $current_page == 'election.php' ? 'active-link' : ''; ?>" href="election.php" <?php echo $current_page == 'election.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
                  <i class="menu-icon mdi mdi-vote" <?php echo $current_page == 'election.php' ? 'style="color: white !important;"' : ''; ?>></i>
                  <span class="menu-title" <?php echo $current_page == 'election.php' ? 'style="color: white !important;"' : ''; ?>>Election</span>
              </a>
          </li>
          <li class="nav-item <?php echo $current_page == 'candidacy.php' ? 'active-link' : ''; ?>">
              <a class="nav-link <?php echo $current_page == 'candidacy.php' ? 'active-link' : ''; ?>" href="candidacy.php" <?php echo $current_page == 'candidacy.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
                  <i class="menu-icon mdi mdi-account-tie" <?php echo $current_page == 'candidacy.php' ? 'style="color: white !important;"' : ''; ?>></i>
                  <span class="menu-title" <?php echo $current_page == 'candidacy.php' ? 'style="color: white !important;"' : ''; ?>>Candidacy</span>
              </a>
          </li>
          <li class="nav-item <?php echo $current_page == 'events.php' ? 'active-link' : ''; ?>">
              <a class="nav-link <?php echo $current_page == 'events.php' ? 'active-link' : ''; ?>" href="events.php" <?php echo $current_page == 'events.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
                  <i class="menu-icon mdi mdi-calendar" <?php echo $current_page == 'events.php' ? 'style="color: white !important;"' : ''; ?>></i>
                  <span class="menu-title" <?php echo $current_page == 'events.php' ? 'style="color: white !important;"' : ''; ?>>Events</span>
              </a>
          </li>
          <li class="nav-item <?php echo $current_page == 'precincts.php' ? 'active-link' : ''; ?>">
              <a class="nav-link <?php echo $current_page == 'precincts.php' ? 'active-link' : ''; ?>" href="precincts.php" <?php echo $current_page == 'precincts.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
                  <i class="mdi mdi-room-service menu-icon" <?php echo $current_page == 'precincts.php' ? 'style="color: white !important;"' : ''; ?>></i>
                  <span class="menu-title" <?php echo $current_page == 'precincts.php' ? 'style="color: white !important;"' : ''; ?>>Precincts</span>
              </a>
          </li>
          <li class="nav-item <?php echo $current_page == 'moderators.php' ? 'active-link' : ''; ?>">
              <a class="nav-link <?php echo $current_page == 'moderators.php' ? 'active-link' : ''; ?>" href="moderators.php" <?php echo $current_page == 'moderators.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
                  <i class="mdi mdi-pac-man menu-icon" <?php echo $current_page == 'moderators.php' ? 'style="color: white !important;"' : ''; ?>></i>
                  <span class="menu-title" <?php echo $current_page == 'moderators.php' ? 'style="color: white !important;"' : ''; ?>>Moderators</span>
              </a>
          </li>
          <li class="nav-item <?php echo $current_page == 'voting.php' ? 'active-link' : ''; ?>">
              <a class="nav-link <?php echo $current_page == 'voting.php' ? 'active-link' : ''; ?>" href="voting.php" <?php echo $current_page == 'voting.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
                  <i class="menu-icon mdi mdi-ballot" <?php echo $current_page == 'voting.php' ? 'style="color: white !important;"' : ''; ?>></i>
                  <span class="menu-title" <?php echo $current_page == 'voting.php' ? 'style="color: white !important;"' : ''; ?>>Voting</span>
              </a>
          </li>

          <li class="nav-item <?php echo $current_page == 'reports.php' ? 'active-link' : ''; ?>">
              <a class="nav-link <?php echo $current_page == 'reports.php' ? 'active-link' : ''; ?>" href="reports.php" <?php echo $current_page == 'reports.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
                  <i class="menu-icon mdi mdi-file-chart" <?php echo $current_page == 'reports.php' ? 'style="color: white !important;"' : ''; ?>></i>
                  <span class="menu-title" <?php echo $current_page == 'reports.php' ? 'style="color: white !important;"' : ''; ?>>Reports</span>
              </a>
          </li>
          <li class="nav-item <?php echo $current_page == 'history.php' ? 'active-link' : ''; ?>">
              <a class="nav-link <?php echo $current_page == 'history.php' ? 'active-link' : ''; ?>" href="history.php" <?php echo $current_page == 'history.php' ? 'style="background-color: #B22222 !important; color: white !important;"' : ''; ?>>
                  <i class="menu-icon mdi mdi-history" <?php echo $current_page == 'history.php' ? 'style="color: white !important;"' : ''; ?>></i>
                  <span class="menu-title" <?php echo $current_page == 'history.php' ? 'style="color: white !important;"' : ''; ?>>History</span>
              </a>
          </li>
      </ul>
  </nav>
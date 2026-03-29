   $votingPeriodId = $votingPeriod['id'];
                                                $votingPeriodName = $votingPeriod['name'];
                                                $votingPeriodStart = $votingPeriod['start_period'];
                                                $votingPeriodEnd = $votingPeriod['end_period'] ?: 'TBD';
                                                $votingPeriodStatus = $votingPeriod['status'];

                                                $votingPeriodReStart = $votingPeriod['re_start_period'] ?: null;
                                                $votingPeriodReEnd = $votingPeriod['re_end_period'] ?: 'null';

                                                if (isset($votingPeriodReStart) && isset($votingPeriodReEnd)) {

                                                    $remaining_seconds = 0;
                                                    if ($votingPeriodStatus === 'Ongoing') {
                                                        $remaining_seconds = strtotime($votingPeriodReEnd) - strtotime($current_date);
                                                        $remaining_seconds = $remaining_seconds < 0 ? 0 : $remaining_seconds;
                                                    } elseif ($votingPeriodStatus === 'Scheduled') {
                                                        $remaining_seconds = strtotime($votingPeriodReStart) - strtotime($current_date);
                                                        $remaining_seconds = $remaining_seconds < 0 ? 0 : $remaining_seconds;
                                                    }
                                                } else {
                                                    $remaining_seconds = 0;
                                                    if ($votingPeriodStatus === 'Ongoing') {
                                                        $remaining_seconds = strtotime($votingPeriodEnd) - strtotime($current_date);
                                                        $remaining_seconds = $remaining_seconds < 0 ? 0 : $remaining_seconds;
                                                    } elseif ($votingPeriodStatus === 'Scheduled') {
                                                        $remaining_seconds = strtotime($votingPeriodStart) - strtotime($current_date);
                                                        $remaining_seconds = $remaining_seconds < 0 ? 0 : $remaining_seconds;
                                                    }
                                                }

                                                
<?php

$vlan_ports = [];
$vlans_data = [];

foreach (dbFetchRows('SELECT * FROM `vlans` WHERE `device_id` = ? GROUP BY `vlan_vlan` ORDER BY `vlan_vlan`', [$device['device_id']]) as $vlan) {
    foreach ($vlan as $k => $v) {
        if ($k != 'vlan_vlan') {
            $vlans_data[$vlan['vlan_vlan']][$k] = $v;
        }
    }
}

$otherports = dbFetchRows('SELECT * FROM `ports_vlans` AS V, `ports` AS P WHERE V.`device_id` = ? AND P.port_id = V.port_id', [$device['device_id']]);
foreach ($otherports as $n => $otherport) {
    if (! $otherport['untagged']) {
        if ($otherport['ifvlan'] == $otherport['vlan']) {
            $otherport['untagged'] = 1;
        }
    }
    $vlan_ports[$otherport['vlan']][$otherport['ifIndex']] = $otherport;
}

ksort($vlan_ports);

$i = 0;
foreach ($vlan_ports as $vlan => $ports) {
    $bg_colour = \LibreNMS\Config::get('list_colour.odd');
    if (! is_integer($i / 2)) {
        $bg_colour = \LibreNMS\Config::get('list_colour.even');
    }

    echo "<tr bgcolor='$bg_colour'>";
    echo '<td width=100 class=list-large> Vlan ' . $vlan . '</td>';
    echo '<td width=200 class=box-desc>' . $vlans_data[$vlan]['vlan_name'] . '</td>';
    echo '<td class=list-bold>';

    foreach ($ports as $port_id => $port) {
        $port = cleanPort($port, $device);
        if ($vars['view'] == 'graphs') {
            echo "<div style='display: block; padding: 2px; margin: 2px; min-width: 139px; max-width:139px; min-height:85px; max-height:85px; text-align: center; float: left; background-color: " . \LibreNMS\Config::get('list_colour.odd_alt2') . ";'>
        <div style='font-weight: bold;'>" . makeshortif($port['ifDescr']) . "</div>
        <a href='device/device=" . $device['device_id'] . '/tab=port/port=' . $port['port_id'] . "/' onmouseover=\"return overlib('\
        <div style=\'font-size: 16px; padding:5px; font-weight: bold; color: #e5e5e5;\'>" . $device['hostname'] . ' - ' . $port['ifDescr'] . '</div>\
        ' . display($port['ifAlias']) . " \
        <img src=\'graph.php?type=$graph_type&amp;id=" . $port['port_id'] . '&amp;from=' . \LibreNMS\Config::get('time.twoday') . '&amp;to=' . \LibreNMS\Config::get('time.now') . "&amp;width=450&amp;height=150\'>\
        ', CENTER, LEFT, FGCOLOR, '#e5e5e5', BGCOLOR, '#e5e5e5', WIDTH, 400, HEIGHT, 150);\" onmouseout=\"return nd();\"  >" . "<img src='graph.php?type=$graph_type&amp;id=" . $port['port_id'] . '&amp;from=' . \LibreNMS\Config::get('time.twoday') . '&amp;to=' . \LibreNMS\Config::get('time.now') . "&amp;width=132&amp;height=40&amp;legend=no'>
        </a>
        <div style='font-size: 9px;'>" . substr(short_port_descr($port['ifAlias']), 0, 22) . '</div>
       </div>';
        } else {
            echo $vlans_data[$vlan]['port_sep'] . generate_port_link($port, makeshortif($port['label']));
            $vlans_data[$vlan]['port_sep'] = ', ';
            if ($port['untagged']) {
                echo '(U)';
            }
        }
    }
    echo '</td></tr>';
    $i++;
}

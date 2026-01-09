select p.id, p.name, p.class, p.active, p.status, p.address, p.updated,
    if(l.point_id is null, json_object(),
        json_objectagg(coalesce(l.status, ''), l.date)) as dates,
    if(pp.point_id is null, json_object(),
        json_objectagg(coalesce(pp.name, ''), pp.value)) as params,
    if(up.point_id is null, json_object(),
        json_objectagg(coalesce(auth.id, ''), up.admin)) as users,
    json_object('id', tg.botid, 'key', tg.botkey, 'chat', tg.chat) as tg
from points p
    left join (select point_id, status, max(date) as date from points_log
        group by point_id, status order by status) l on l.point_id = p.id
    left join points_params pp on p.id = pp.point_id
    left join user_points up on up.point_id = p.id
    left join auth on auth.id = up.user_id
    left join tg on tg.id = p.tg_id
group by p.id order by name, class

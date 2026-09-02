import { Form, Head, Link, router } from '@inertiajs/react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Pagination, type Paginated } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

type Level = { id: number; name: string };
type LevelGroup = { id: number; name: string; levels: Level[] };
type Teacher = {
    id: number;
    name: string;
    phone: string;
    is_active: boolean;
    reservations_count: number;
    subjects: { id: number; name: string }[];
};

const selectClass =
    'border-input text-foreground h-10 w-full rounded-md border bg-background px-3 text-sm sm:w-64';

export default function TeachersIndex({
    teachers,
    levelGroups,
    filters,
}: {
    teachers: Paginated<Teacher>;
    levelGroups: LevelGroup[];
    filters: { group: number | null };
}) {
    const onFilterChange = (value: string) => {
        router.get(
            '/admin/teachers',
            value === '' ? {} : { group: Number(value) },
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="المعلمون" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="المعلمون"
                    description="أضف المعلمين وحدد المواد التي يدرّسونها ومواعيدهم."
                    actions={
                        <Button asChild>
                            <Link href="/admin/teachers/create">
                                إضافة معلم
                            </Link>
                        </Button>
                    }
                />

                <div className="grid gap-2">
                    <Label>تصفية حسب مجموعة المرحلة</Label>
                    <select
                        className={selectClass}
                        value={filters.group ?? ''}
                        onChange={(e) => onFilterChange(e.target.value)}
                    >
                        <option value="">كل المجموعات</option>
                        {levelGroups.map((group) => (
                            <option key={group.id} value={group.id}>
                                {group.name}
                            </option>
                        ))}
                    </select>
                </div>

                {teachers.data.length === 0 ? (
                    <EmptyState
                        title="لا يوجد معلمون بعد."
                        description="ابدأ بإضافة أول معلم."
                        action={
                            <Button asChild>
                                <Link href="/admin/teachers/create">
                                    إضافة معلم
                                </Link>
                            </Button>
                        }
                    />
                ) : (
                    <>
                        <div className="overflow-hidden rounded-xl border bg-card shadow-sm">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/60 text-start">
                                    <tr>
                                        <th className="p-3 font-medium">الاسم</th>
                                        <th className="p-3 font-medium">الهاتف</th>
                                        <th className="p-3 font-medium">المواد</th>
                                        <th className="p-3 font-medium">الحجوزات</th>
                                        <th className="p-3 font-medium">الحالة</th>
                                        <th className="p-3" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {teachers.data.map((teacher) => (
                                        <tr
                                            key={teacher.id}
                                            className="border-t transition-colors hover:bg-muted/30"
                                        >
                                            <td className="p-3 font-medium">
                                                {teacher.name}
                                            </td>
                                            <td
                                                className="p-3 tabular-nums"
                                                dir="ltr"
                                            >
                                                {teacher.phone}
                                            </td>
                                            <td className="p-3">
                                                <div className="flex flex-wrap gap-1">
                                                    {teacher.subjects.map(
                                                        (subject) => (
                                                            <span
                                                                key={subject.id}
                                                                className="rounded-full bg-secondary px-2 py-0.5 text-xs text-secondary-foreground"
                                                            >
                                                                {subject.name}
                                                            </span>
                                                        ),
                                                    )}
                                                </div>
                                            </td>
                                            <td className="p-3">
                                                {teacher.reservations_count}
                                            </td>
                                            <td className="p-3">
                                                <span
                                                    className={
                                                        teacher.is_active
                                                            ? 'rounded-full bg-emerald-100 px-2 py-0.5 text-xs text-emerald-700'
                                                            : 'rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground'
                                                    }
                                                >
                                                    {teacher.is_active
                                                        ? 'نشط'
                                                        : 'غير نشط'}
                                                </span>
                                            </td>
                                            <td className="p-3 text-end">
                                                <div className="flex justify-end gap-2">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={`/admin/teachers/${teacher.id}`}
                                                        >
                                                            التفاصيل
                                                        </Link>
                                                    </Button>
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={`/admin/teachers/${teacher.id}/edit`}
                                                        >
                                                            تعديل
                                                        </Link>
                                                    </Button>
                                                    <Form
                                                        action={`/admin/teachers/${teacher.id}`}
                                                        method="delete"
                                                    >
                                                        {({ processing }) => (
                                                            <Button
                                                                variant="destructive"
                                                                size="sm"
                                                                disabled={
                                                                    processing
                                                                }
                                                            >
                                                                حذف
                                                            </Button>
                                                        )}
                                                    </Form>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <Pagination meta={teachers} />
                    </>
                )}
            </div>
        </>
    );
}

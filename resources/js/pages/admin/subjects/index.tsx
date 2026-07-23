import { Form, Head, Link, router } from '@inertiajs/react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Pagination, type Paginated } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';

type Level = { id: number; name: string };
type Subject = {
    id: number;
    name: string;
    teachers_count: number;
    education_levels: Level[];
};

const selectClass =
    'border-input text-foreground h-10 w-full rounded-md border bg-background px-3 text-sm sm:w-64';

export default function SubjectsIndex({
    subjects,
    levels,
    filters,
}: {
    subjects: Paginated<Subject>;
    levels: Level[];
    filters: { level: number | null };
}) {
    const onFilterChange = (value: string) => {
        router.get(
            '/admin/subjects',
            value === '' ? {} : { level: Number(value) },
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="المواد" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="المواد"
                    description="أضف المواد الدراسية وحدد المراحل التي تُدرّس فيها."
                    actions={
                        <Button asChild>
                            <Link href="/admin/subjects/create">إضافة مادة</Link>
                        </Button>
                    }
                />

                <div className="grid gap-2">
                    <Label>تصفية حسب المرحلة الدراسية</Label>
                    <select
                        className={selectClass}
                        value={filters.level ?? ''}
                        onChange={(e) => onFilterChange(e.target.value)}
                    >
                        <option value="">كل المراحل</option>
                        {levels.map((level) => (
                            <option key={level.id} value={level.id}>
                                {level.name}
                            </option>
                        ))}
                    </select>
                </div>

                {subjects.data.length === 0 ? (
                    <EmptyState
                        title="لا توجد مواد بعد."
                        description="ابدأ بإضافة أول مادة دراسية."
                        action={
                            <Button asChild>
                                <Link href="/admin/subjects/create">
                                    إضافة مادة
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
                                    <th className="p-3 font-medium">
                                        المراحل الدراسية
                                    </th>
                                    <th className="p-3 font-medium">
                                        عدد المعلمين
                                    </th>
                                    <th className="p-3" />
                                </tr>
                            </thead>
                            <tbody>
                                {subjects.data.map((subject) => (
                                    <tr
                                        key={subject.id}
                                        className="border-t transition-colors hover:bg-muted/30"
                                    >
                                        <td className="p-3 font-medium">
                                            {subject.name}
                                        </td>
                                        <td className="p-3">
                                            <div className="flex flex-wrap gap-1">
                                                {subject.education_levels.map(
                                                    (level) => (
                                                        <span
                                                            key={level.id}
                                                            className="rounded-full bg-secondary px-2 py-0.5 text-xs text-secondary-foreground"
                                                        >
                                                            {level.name}
                                                        </span>
                                                    ),
                                                )}
                                            </div>
                                        </td>
                                        <td className="p-3">
                                            {subject.teachers_count}
                                        </td>
                                        <td className="p-3 text-end">
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/admin/subjects/${subject.id}/edit`}
                                                    >
                                                        تعديل
                                                    </Link>
                                                </Button>
                                                <Form
                                                    action={`/admin/subjects/${subject.id}`}
                                                    method="delete"
                                                >
                                                    {({ processing }) => (
                                                        <Button
                                                            variant="destructive"
                                                            size="sm"
                                                            disabled={processing}
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
                    <Pagination meta={subjects} />
                    </>
                )}
            </div>
        </>
    );
}

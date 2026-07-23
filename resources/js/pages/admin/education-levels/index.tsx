import { Form, Head, Link } from '@inertiajs/react';
import { EmptyState } from '@/components/empty-state';
import { PageHeader } from '@/components/page-header';
import { Pagination, type Paginated } from '@/components/pagination';
import { Button } from '@/components/ui/button';

type Level = {
    id: number;
    name: string;
};

export default function EducationLevelsIndex({
    levels,
}: {
    levels: Paginated<Level>;
}) {
    return (
        <>
            <Head title="المراحل الدراسية" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="المراحل الدراسية"
                    description="أضف الصفوف والمراحل الدراسية التي يقدمها المركز."
                    actions={
                        <Button asChild>
                            <Link href="/admin/education-levels/create">
                                إضافة مرحلة
                            </Link>
                        </Button>
                    }
                />

                {levels.data.length === 0 ? (
                    <EmptyState
                        title="لا توجد مراحل دراسية بعد."
                        description="ابدأ بإضافة أول مرحلة دراسية."
                        action={
                            <Button asChild>
                                <Link href="/admin/education-levels/create">
                                    إضافة مرحلة
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
                                    <th className="p-3" />
                                </tr>
                            </thead>
                            <tbody>
                                {levels.data.map((level) => (
                                    <tr
                                        key={level.id}
                                        className="border-t transition-colors hover:bg-muted/30"
                                    >
                                        <td className="p-3 font-medium">
                                            {level.name}
                                        </td>
                                        <td className="p-3 text-end">
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={`/admin/education-levels/${level.id}/edit`}
                                                    >
                                                        تعديل
                                                    </Link>
                                                </Button>
                                                <Form
                                                    action={`/admin/education-levels/${level.id}`}
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
                    <Pagination meta={levels} />
                    </>
                )}
            </div>
        </>
    );
}
